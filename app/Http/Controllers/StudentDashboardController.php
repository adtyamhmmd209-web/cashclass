<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ClassFeeAssignment;
use App\Models\ClassModel;
use App\Models\LessonSchedule;
use App\Models\PaymentAccount;
use App\Models\PaymentSubmission;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ClassNotifier;
use App\Services\SchoolContext;
use App\Notifications\PaymentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $class = SchoolContext::forUser(auth()->user());

        return view('student.overview', [
            'class' => $class,
            'balance' => Transaction::ofType('income')->sum('amount') - Transaction::ofType('expense')->sum('amount'),
            'paid' => PaymentSubmission::where('user_id', auth()->id())->where('status', 'approved')->sum('amount'),
            'pending' => PaymentSubmission::where('user_id', auth()->id())->where('status', 'pending')->count(),
            'announcements' => Announcement::orderByDesc('is_pinned')->latest()->limit(10)->get(),
        ]);
    }

    public function transactions(Request $request)
    {
        $request->validate(['filter' => ['nullable', 'in:all,income,expense']]);
        $query = Transaction::with('category')->orderByDesc('transaction_date')->orderByDesc('id');
        if ($request->input('filter', 'all') !== 'all') {
            $query->where('type', $request->input('filter'));
        }
        $context = SchoolContext::forUser($request->user());
        $query->where('class_id', $context?->id);

        return view('student.transactions', [
            'announcements' => Announcement::where('class_id', $context?->id)->orderByDesc('is_pinned')->latest()->limit(10)->get(),
            'transactions' => $query->paginate(10)->withQueryString(),
            'payments' => PaymentSubmission::where('user_id', $request->user()->id)->latest()->paginate(10, ['*'], 'payments_page'),
        ]);
    }

    public function fees(Request $request)
    {
        $assignments = ClassFeeAssignment::with(['classFee', 'payments'])
            ->where('user_id', $request->user()->id)
            ->whereHas('classFee', fn ($query) => $query->where('starts_at', '<=', today()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('student.fees', compact('assignments'));
    }

    public function createTransaction(Request $request)
    {
        $class = SchoolContext::forUser($request->user());
        $feeAssignment = null;
        if ($request->filled('class_fee_assignment_id')) {
            abort_unless($class, 404);
            $feeAssignment = ClassFeeAssignment::with(['classFee', 'payments'])
                ->where('class_id', $class->id)
                ->where('user_id', $request->user()->id)
                ->findOrFail($request->integer('class_fee_assignment_id'));
            abort_if($feeAssignment->classFee->starts_at->isFuture(), 404);
            abort_if($feeAssignment->payments->contains('status', 'approved') || $feeAssignment->payments->contains('status', 'pending'), 409, 'Tagihan ini sudah dibayar atau menunggu verifikasi.');
        }

        return view('student.create-transaction', [
            'accounts' => $class ? PaymentAccount::where('class_id', $class->id)->where('is_active', true)->orderBy('type')->orderBy('provider')->get() : collect(),
            'feeAssignment' => $feeAssignment,
        ]);
    }

    public function storeTransaction(Request $request)
    {
        $class = SchoolContext::requireForUser($request->user());
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:1000', 'max:999999999'],
            'description' => ['required', 'string', 'max:255'],
            'method' => ['required', 'in:bank,e_wallet'],
            'payment_account_id' => ['required', 'integer', Rule::exists('payment_accounts', 'id')->where('class_id', $class->id)->where('is_active', true)->where('type', $request->input('method'))],
            'class_fee_assignment_id' => ['nullable', 'integer'],
            'submission_key' => ['required', 'uuid', 'unique:payment_submissions,submission_key'],
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], ['method.in' => 'Siswa hanya dapat mengirim transfer bank atau e-wallet. Pembayaran cash dicatat langsung oleh bendahara.', 'proof.required' => 'Unggah bukti transfer terlebih dahulu.', 'submission_key.unique' => 'Bukti pembayaran ini sudah pernah dikirim. Periksa riwayat pembayaran Anda sebelum mengirim lagi.']);
        $proof = null;
        try {
            $proof = $request->file('proof')->store('payment-proofs', 'public');
            if (! $proof) {
                throw new \RuntimeException('Bukti transfer gagal disimpan.');
            }
            DB::transaction(function () use ($request, $class, $data, $proof) {
                ClassModel::whereKey($class->id)->lockForUpdate()->firstOrFail();
                $account = PaymentAccount::whereKey($data['payment_account_id'])->where('class_id', $class->id)->where('type', $data['method'])->where('is_active', true)->lockForUpdate()->first();
                if (! $account) {
                    throw ValidationException::withMessages(['payment_account_id' => 'Tujuan pembayaran sudah tidak aktif. Pilih tujuan yang tersedia.']);
                }
                $feeAssignment = null;
                if (! empty($data['class_fee_assignment_id'])) {
                    $feeAssignment = ClassFeeAssignment::whereKey($data['class_fee_assignment_id'])
                        ->where('class_id', $class->id)
                        ->where('user_id', $request->user()->id)
                        ->with('classFee')
                        ->lockForUpdate()
                        ->first();
                    if (! $feeAssignment || $feeAssignment->classFee->starts_at->isFuture()) {
                        throw ValidationException::withMessages(['class_fee_assignment_id' => 'Tagihan tidak tersedia untuk akun Anda.']);
                    }
                    if ($this->toMinor($feeAssignment->classFee->amount) !== $this->toMinor($data['amount'])) {
                        throw ValidationException::withMessages(['amount' => 'Nominal transfer harus sama dengan jumlah tagihan.']);
                    }
                    if ($feeAssignment->payments()->whereIn('status', ['pending', 'approved'])->exists()) {
                        throw ValidationException::withMessages(['class_fee_assignment_id' => 'Tagihan ini sudah dibayar atau menunggu verifikasi.']);
                    }
                }
                PaymentSubmission::create([
                    'user_id' => $request->user()->id, 'class_id' => $class->id, 'payment_date' => $data['date'],
                    'amount' => $data['amount'], 'description' => $data['description'], 'proof_image' => $proof,
                    'method' => $data['method'], 'payment_account_id' => $account->id,
                    'payment_provider' => $account->provider, 'destination_number' => $account->account_number, 'destination_holder' => $account->account_holder,
                    'submission_key' => $data['submission_key'], 'class_fee_assignment_id' => $feeAssignment?->id,
                ]);
                $methodLabel = $data['method'] === 'bank' ? 'transfer bank' : 'e-wallet';
                $message = $request->user()->name.' mengirim pembayaran Rp '.number_format($data['amount'], 0, ',', '.').' melalui '.$methodLabel.'.';
                if ($class->treasurer_id) {
                    User::find($class->treasurer_id)?->notify(new PaymentNotification([
                        'title' => 'Pembayaran baru menunggu verifikasi',
                        'message' => $message,
                        'url' => route('payments.index'),
                    ]));
                }
                (new ClassNotifier)->waliKelas($class, 'Pembayaran baru menunggu verifikasi', $message, route('walikelas.reports'));
            });
        } catch (ValidationException $e) {
            if ($proof) {
                Storage::disk('public')->delete($proof);
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($proof) {
                Storage::disk('public')->delete($proof);
            }
            report($e);

            return back()->withInput()->with('error', 'Transfer belum terkirim. Silakan coba lagi.');
        }

        return redirect()->route('student.transactions')->with('status', 'Bukti transfer terkirim dan menunggu verifikasi bendahara.');
    }

    private function toMinor($value): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public function proof(Request $request, PaymentSubmission $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        return app(PaymentController::class)->proof($payment);
    }

    public function schedule()
    {
        $class = SchoolContext::forUser(request()->user());
        $records = LessonSchedule::where('class_id', $class?->id)->orderBy('weekday')->orderBy('start_time')->get();

        return view('student.schedule', compact('class', 'records'));
    }

    public function profile()
    {
        return redirect()->route('profile.edit');
    }

    public function settings()
    {
        return redirect()->route('profile.edit');
    }
}
