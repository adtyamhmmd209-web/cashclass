<?php

namespace App\Http\Controllers;

use App\Models\ClassFeeAssignment;
use App\Models\ClassModel;
use App\Models\PaymentSubmission;
use App\Models\User;
use App\Services\SchoolContext;
use App\Services\TransactionService;
use App\Notifications\PaymentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'in:all,pending,approved,rejected,refunded'], 'method' => ['nullable', 'in:all,bank,e_wallet,cash']]);
        $query = PaymentSubmission::with(['user', 'class', 'transaction', 'classFeeAssignment.classFee'])->latest();
        if ($request->filled('search')) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%'));
        }
        if ($request->input('status', 'all') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->input('method', 'all') !== 'all') {
            $query->where('method', $request->input('method'));
        }

        $classId = SchoolContext::forUser($request->user())?->id;

        return view('payments.index', [
            'payments' => $query->paginate(15)->withQueryString(),
            'students' => User::where('role', 'siswa')->whereHas('classMembers', fn ($q) => $q->where('class_id', $classId)->where('is_active', true))->orderBy('name')->get(),
            'feeAssignments' => ClassFeeAssignment::with(['classFee', 'user'])
                ->where('class_id', $classId)
                ->whereHas('classFee', fn ($q) => $q->where('starts_at', '<=', today()))
                ->whereDoesntHave('payments', fn ($q) => $q->whereIn('status', ['pending', 'approved']))
                ->get(),
        ]);
    }

    public function storeCash(Request $request, TransactionService $service)
    {
        $class = SchoolContext::requireForUser($request->user());
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('class_members', 'user_id')->where('class_id', $class->id)->where('is_active', true), Rule::exists('users', 'id')->where('role', 'siswa')],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:1000', 'max:999999999'],
            'cash_received' => ['required', 'numeric', 'decimal:0,2', 'gte:amount', 'max:9999999999999.99'],
            'description' => ['required', 'string', 'max:255'], 'submission_key' => ['required', 'uuid'],
            'class_fee_assignment_id' => ['nullable', 'integer'],
        ], [
            'cash_received.required' => 'Uang diterima wajib diisi.',
            'cash_received.gte' => 'Uang diterima tidak boleh kurang dari nominal pembayaran kas.',
            'cash_received.numeric' => 'Uang diterima harus berupa angka.',
            'cash_received.decimal' => 'Uang diterima maksimal dua angka di belakang koma.',
            'amount.decimal' => 'Nominal pembayaran maksimal dua angka di belakang koma.',
        ]);
        // Work in integer minor units so the saved change never depends on floating-point subtraction.
        $toMinor = static function ($value): int {
            [$whole, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '');

            return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
        };
        $received = $toMinor($data['cash_received']);
        $change = $received - $toMinor($data['amount']);
        $data['cash_received'] = sprintf('%d.%02d', intdiv($received, 100), $received % 100);
        $data['cash_change'] = sprintf('%d.%02d', intdiv($change, 100), $change % 100);
        DB::transaction(function () use ($request, $class, $data, $service) {
            ClassModel::whereKey($class->id)->lockForUpdate()->firstOrFail();
            $prior = PaymentSubmission::withoutGlobalScopes()->where('submission_key', $data['submission_key'])->first();
            if ($prior) {
                $feeAssignmentId = isset($data['class_fee_assignment_id']) ? (int) $data['class_fee_assignment_id'] : null;
                abort_unless($prior->class_id === $class->id && $prior->user_id === (int) $data['user_id'] && $prior->method === 'cash' && $prior->reviewed_by === $request->user()->id && $prior->class_fee_assignment_id === $feeAssignmentId && (float) $prior->amount === (float) $data['amount'] && $prior->payment_date->toDateString() === $data['date'] && $prior->description === $data['description'] && $prior->cash_received === $data['cash_received'], 409, 'Permintaan pembayaran sudah digunakan.');

                return;
            }
            $feeAssignment = null;
            if (! empty($data['class_fee_assignment_id'])) {
                $feeAssignment = ClassFeeAssignment::whereKey($data['class_fee_assignment_id'])
                    ->where('class_id', $class->id)
                    ->where('user_id', $data['user_id'])
                    ->with('classFee')
                    ->lockForUpdate()
                    ->first();
                if (! $feeAssignment || $feeAssignment->classFee->starts_at->isFuture()) {
                    throw ValidationException::withMessages(['class_fee_assignment_id' => 'Tagihan tidak tersedia untuk siswa tersebut.']);
                }
                if ($this->toMinor($feeAssignment->classFee->amount) !== $this->toMinor($data['amount'])) {
                    throw ValidationException::withMessages(['amount' => 'Nominal pembayaran harus sama dengan jumlah tagihan.']);
                }
                if ($feeAssignment->payments()->whereIn('status', ['pending', 'approved'])->exists()) {
                    throw ValidationException::withMessages(['class_fee_assignment_id' => 'Tagihan ini sudah dibayar atau menunggu verifikasi.']);
                }
            }
            $student = User::findOrFail($data['user_id']);
            $transaction = $service->save(['date' => $data['date'], 'amount' => $data['amount'], 'type' => 'income', 'category' => 'Pembayaran Kas', 'description' => $student->name.' - '.$data['description'].' (cash)'], $request->user()->id, null, null, $class->id);
            PaymentSubmission::create(['class_id' => $class->id, 'user_id' => $student->id, 'class_fee_assignment_id' => $feeAssignment?->id, 'payment_date' => $data['date'], 'amount' => $data['amount'], 'description' => $data['description'], 'method' => 'cash', 'status' => 'approved', 'reviewed_by' => $request->user()->id, 'transaction_id' => $transaction->id, 'submission_key' => $data['submission_key'], 'cash_received' => $data['cash_received'], 'cash_change' => $data['cash_change']]);
        });

        $cashPayment = PaymentSubmission::withoutGlobalScopes()->where('submission_key', $data['submission_key'])->first();
        $cashPayment?->user->notify(new PaymentNotification([
            'title' => 'Pembayaran cash dicatat',
            'message' => 'Pembayaran tunai Rp '.number_format($cashPayment->amount, 0, ',', '.').' telah dicatat dan masuk ke kas kelas.',
            'url' => route('student.transactions'),
        ]));

        return redirect()->route('payments.index')->with('status', 'Pembayaran cash dicatat dan langsung masuk kas kelas.');
    }

    public function review(Request $request, PaymentSubmission $payment, TransactionService $service)
    {
        SchoolContext::requireForUser($request->user());
        $data = $request->validate(['status' => ['required', 'in:approved,rejected']]);
        $copiedProof = null;
        try {
            DB::transaction(function () use ($request, $payment, $data, $service, &$copiedProof) {
                $payment = PaymentSubmission::whereKey($payment->id)->lockForUpdate()->firstOrFail();
                abort_unless($payment->status === 'pending', 409, 'Pembayaran sudah diproses.');
                $transaction = null;
                if ($data['status'] === 'approved') {
                    $transaction = $service->save([
                        'date' => $payment->payment_date->toDateString(), 'type' => 'income', 'category' => 'Pembayaran Kas',
                        'description' => $payment->user->name.' - '.$payment->description, 'amount' => $payment->amount,
                    ], $request->user()->id, null, null, $payment->class_id);
                    if ($payment->proof_image) {
                        $copiedProof = 'proofs/'.Str::uuid().'.'.pathinfo($payment->proof_image, PATHINFO_EXTENSION);
                        if (! Storage::disk('public')->copy($payment->proof_image, $copiedProof)) {
                            throw new \RuntimeException('Gagal menyalin bukti pembayaran.');
                        }
                        $transaction->update(['proof_image' => $copiedProof]);
                    }
                }
                $payment->update(['status' => $data['status'], 'reviewed_by' => $request->user()->id, 'transaction_id' => $transaction?->id]);
            });
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            if ($copiedProof) {
                Storage::disk('public')->delete($copiedProof);
            }
            report($e);

            return back()->with('error', 'Pembayaran belum berhasil diproses. Silakan coba lagi.');
        }

        $payment->refresh();
        $payment->user->notify(new PaymentNotification([
            'title' => $data['status'] === 'approved' ? 'Pembayaran disetujui' : 'Pembayaran ditolak',
            'message' => 'Pembayaran Rp '.number_format($payment->amount, 0, ',', '.').' berstatus '.($data['status'] === 'approved' ? 'disetujui dan telah masuk ke kas.' : 'ditolak oleh bendahara.'),
            'url' => route('student.transactions'),
        ]));

        return back()->with('status', $data['status'] === 'approved' ? 'Pembayaran disetujui dan dicatat sebagai pemasukan.' : 'Pembayaran ditolak.');
    }

    public function proof(PaymentSubmission $payment)
    {
        abort_unless($payment->proof_image && Storage::disk('public')->exists($payment->proof_image), 404);

        return Storage::disk('public')->response($payment->proof_image);
    }

    public function refund(Request $request, PaymentSubmission $payment, TransactionService $service)
    {
        $class = SchoolContext::requireForUser($request->user());

        try {
            DB::transaction(function () use ($request, $payment, $service, $class) {
                $payment = PaymentSubmission::withoutGlobalScopes()->whereKey($payment->id)
                    ->where('class_id', $class->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_unless($payment->method === 'cash', 409, 'Pengembalian hanya tersedia untuk pembayaran cash.');
                abort_unless($payment->status === 'approved', 409, 'Pembayaran cash belum disetujui atau sudah dikembalikan.');
                abort_unless($payment->transaction_id, 409, 'Transaksi pembayaran cash tidak ditemukan.');

                $originalTransaction = $payment->transaction()->lockForUpdate()->firstOrFail();
                abort_unless($originalTransaction->class_id === $class->id && $originalTransaction->type === 'income', 409, 'Transaksi pembayaran tidak dapat dikembalikan.');

                $refundTransaction = $service->save([
                    'date' => now()->toDateString(),
                    'amount' => $payment->amount,
                    'type' => 'expense',
                    'category' => 'Pengembalian Kas',
                    'description' => 'Pengembalian cash - '.$payment->user->name.' - '.$payment->description,
                ], $request->user()->id, null, null, $class->id);

                $payment->update([
                    'status' => 'refunded',
                    'reviewed_by' => $request->user()->id,
                    'transaction_id' => $refundTransaction->id,
                ]);
            });
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Pembayaran cash belum berhasil dikembalikan. Silakan coba lagi.');
        }

        $payment->refresh();
        $payment->user->notify(new PaymentNotification([
            'title' => 'Pembayaran cash dikembalikan',
            'message' => 'Pengembalian Rp '.number_format($payment->amount, 0, ',', '.').' telah dicatat dan saldo kas dikurangi.',
            'url' => route('student.transactions'),
        ]));

        return back()->with('status', 'Pengembalian cash dicatat. Saldo kas telah dikurangi dan status pembayaran diperbarui.');
    }

    private function toMinor($value): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
