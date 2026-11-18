<?php

namespace App\Http\Controllers;

use App\Models\ClassFee;
use App\Models\ClassMember;
use App\Models\ClassModel;
use App\Models\User;
use App\Notifications\FinanceNotification;
use App\Services\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassFeeController extends Controller
{
    public function index(Request $request)
    {
        $class = SchoolContext::requireForUser($request->user());
        $fees = ClassFee::where('class_id', $class->id)
            ->with(['assignments.user', 'assignments.payments'])
            ->latest('starts_at')
            ->paginate(15)
            ->withQueryString();

        return view('class-fees.index', compact('fees'));
    }

    public function create(Request $request)
    {
        $class = SchoolContext::requireForUser($request->user());
        $studentCount = User::where('role', 'siswa')
            ->whereHas('classMembers', fn ($query) => $query->where('class_id', $class->id)->where('is_active', true))
            ->count();

        return view('class-fees.create', compact('studentCount'));
    }

    public function store(Request $request)
    {
        $class = SchoolContext::requireForUser($request->user());
        $data = $this->validated($request);

        $fee = null;
        DB::transaction(function () use ($request, $class, $data, &$fee) {
            ClassModel::whereKey($class->id)->lockForUpdate()->firstOrFail();
            $studentIds = ClassMember::where('class_id', $class->id)
                ->where('is_active', true)
                ->whereHas('user', fn ($query) => $query->where('role', 'siswa'))
                ->lockForUpdate()
                ->pluck('user_id');

            if ($studentIds->isEmpty()) {
                throw ValidationException::withMessages(['class' => 'Tambahkan siswa ke kelas sebelum membuat tagihan.']);
            }

            $fee = ClassFee::create($data + ['class_id' => $class->id, 'created_by' => $request->user()->id]);
            $fee->assignments()->createMany($studentIds->map(fn ($userId) => ['class_id' => $class->id, 'user_id' => $userId])->all());
        });

        $this->notifyStudents($fee, 'Tagihan baru', 'Tagihan "'.$fee->title.'" sebesar Rp '.number_format((float) $fee->amount, 0, ',', '.').' telah dibuat. Jatuh tempo '.$fee->due_date->translatedFormat('d F Y').'.');

        return redirect()->route('class-fees.index')->with('status', 'Tagihan dibuat untuk siswa yang terdaftar saat ini.');
    }

    public function edit(Request $request, ClassFee $classFee)
    {
        $this->ensureClassOwnership($request, $classFee);
        abort_if($classFee->assignments()->whereHas('payments')->exists(), 409, 'Tagihan dengan riwayat pembayaran tidak dapat diubah.');

        return view('class-fees.edit', ['fee' => $classFee]);
    }

    public function update(Request $request, ClassFee $classFee)
    {
        $this->ensureClassOwnership($request, $classFee);
        $data = $this->validated($request);

        $fee = null;
        DB::transaction(function () use ($classFee, $data, &$fee) {
            ClassModel::whereKey($classFee->class_id)->lockForUpdate()->firstOrFail();
            $fee = ClassFee::whereKey($classFee->id)->lockForUpdate()->firstOrFail();
            if ($fee->assignments()->whereHas('payments')->exists()) {
                throw ValidationException::withMessages(['fee' => 'Tagihan dengan riwayat pembayaran tidak dapat diubah.']);
            }
            $fee->update($data);
        });

        $this->notifyStudents($fee, 'Tagihan diperbarui', 'Tagihan "'.$fee->title.'" telah diperbarui menjadi Rp '.number_format((float) $fee->amount, 0, ',', '.').'. Jatuh tempo '.$fee->due_date->translatedFormat('d F Y').'.');

        return redirect()->route('class-fees.index')->with('status', 'Tagihan berhasil diperbarui.');
    }

    public function destroy(Request $request, ClassFee $classFee)
    {
        $this->ensureClassOwnership($request, $classFee);
        $recipients = $classFee->assignments()->with('user')->get()->pluck('user')->filter()->all();
        DB::transaction(function () use ($classFee) {
            ClassModel::whereKey($classFee->class_id)->lockForUpdate()->firstOrFail();
            $fee = ClassFee::whereKey($classFee->id)->lockForUpdate()->firstOrFail();
            if ($fee->assignments()->whereHas('payments')->exists()) {
                abort(409, 'Tagihan dengan riwayat pembayaran tidak dapat dihapus.');
            }
            $fee->delete();
        });

        $this->notifyUsers($recipients, 'Tagihan dihapus', 'Tagihan "'.$classFee->title.'" telah dihapus oleh bendahara.');

        return redirect()->route('class-fees.index')->with('status', 'Tagihan berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:1000', 'max:999999999'],
            'starts_at' => ['required', 'date_format:Y-m-d'],
            'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function notifyStudents(?ClassFee $fee, string $title, string $message): void
    {
        if (! $fee) {
            return;
        }

        $this->notifyUsers(
            $fee->assignments()->with('user')->get()->pluck('user')->filter()->all(),
            $title,
            $message,
        );
    }

    private function notifyUsers(iterable $users, string $title, string $message): void
    {
        foreach ($users as $student) {
            $student->notify(new FinanceNotification([
                'title' => $title,
                'message' => $message,
                'url' => route('student.fees'),
            ]));
        }
    }

    private function ensureClassOwnership(Request $request, ClassFee $fee): void
    {
        abort_unless($fee->class_id === SchoolContext::requireForUser($request->user())->id, 404);
    }
}
