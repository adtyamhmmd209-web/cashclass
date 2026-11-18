<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ClassFeeAssignment;
use App\Models\ClassModel;
use App\Models\PaymentSubmission;
use App\Models\User;
use App\Services\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $class = SchoolContext::forUser($request->user());
        if (! $class) {
            return view('students.index', ['students' => User::whereRaw('0 = 1')->paginate(15), 'class' => null]);
        }
        $query = User::whereIn('role', ['siswa', 'bendahara'])
            ->whereHas('classMembers', fn ($q) => $q->where('class_id', $class->id)->where('is_active', true))
            ->with('classMembers.class')
            ->orderBy('name');
        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('nisn', 'like', $search)->orWhere('username', 'like', $search)->orWhere('email', 'like', $search));
        }

        return view('students.index', ['students' => $query->paginate(15)->withQueryString(), 'class' => $class]);
    }

    public function show(Request $request, User $student)
    {
        $this->ensureManagedAccount($student, $this->managedClass($request));

        return view('students.show', compact('student'));
    }

    public function edit(Request $request, User $student)
    {
        $class = $this->managedClass($request);
        $this->ensureManagedAccount($student, $class);

        return view('students.edit', compact('student', 'class'));
    }

    public function update(Request $request, User $student)
    {
        $class = $this->managedClass($request);
        $this->ensureManagedAccount($student, $class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nisn' => ['required', 'string', 'size:10', 'regex:/^[0-9]+$/', Rule::unique('users', 'nisn')->ignore($student->id)],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($student->id)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], [
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.size' => 'NISN harus terdiri dari 10 digit.',
            'nisn.regex' => 'NISN hanya boleh berisi angka.',
            'nisn.unique' => 'NISN sudah digunakan oleh akun lain.',
        ]);

        $attributes = [
            'name' => $data['name'],
            'nisn' => $data['nisn'],
            'username' => $data['username'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ];
        if ($request->filled('password')) {
            $attributes['password'] = $data['password'];
        }

        DB::transaction(function () use ($student, $attributes) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail()->update($attributes);
        });

        return redirect()->route('students.index')->with('status', 'Akun '.$student->name.' berhasil diperbarui.');
    }

    public function destroy(Request $request, User $student)
    {
        $class = $this->managedClass($request);
        $this->ensureManagedAccount($student, $class);

        if ($request->user()->is($student)) {
            return back()->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan.');
        }

        if ($this->hasClassData($student)) {
            return back()->with('error', 'Akun '.$student->name.' tidak dapat dihapus karena sudah memiliki riwayat transaksi, pembayaran, tagihan, atau pengumuman. Riwayat tersebut perlu dipertahankan agar laporan kas tetap utuh.');
        }

        $linkedElsewhere = ClassModel::where('id', '!=', $class->id)->where('treasurer_id', $student->id)->exists()
            || $student->classMembers()->where('class_id', '!=', $class->id)->where('is_active', true)->exists();

        if ($linkedElsewhere) {
            return back()->with('error', 'Akun '.$student->name.' masih terhubung dengan kelas lain sehingga belum dapat dihapus.');
        }

        DB::transaction(function () use ($student, $class) {
            $lockedClass = ClassModel::whereKey($class->id)->lockForUpdate()->firstOrFail();
            $account = User::whereKey($student->id)->lockForUpdate()->firstOrFail();

            if ($lockedClass->treasurer_id === $account->id) {
                $lockedClass->update(['treasurer_id' => null]);
            }

            $account->delete();
        });

        return redirect()->route('students.index')->with('status', 'Akun '.$student->name.' berhasil dihapus.');
    }

    private function managedClass(Request $request): ClassModel
    {
        $class = SchoolContext::forUser($request->user());
        abort_unless($class, 403, 'Kelas belum tersedia untuk akun ini.');

        return $class;
    }

    private function ensureManagedAccount(User $student, ClassModel $class): void
    {
        abort_unless(in_array($student->role, ['siswa', 'bendahara'], true), 404);
        abort_unless($student->classMembers()->where('class_id', $class->id)->where('is_active', true)->exists(), 404);
    }

    private function hasClassData(User $student): bool
    {
        return $student->transactions()->exists()
            || PaymentSubmission::where('user_id', $student->id)->exists()
            || ClassFeeAssignment::where('user_id', $student->id)->exists()
            || Announcement::where('user_id', $student->id)->exists();
    }
}
