<?php

namespace App\Http\Controllers;

use App\Models\ClassMember;
use App\Models\ClassModel;
use App\Models\User;
use App\Services\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class WaliAccountController extends Controller
{
    public function accounts(Request $request)
    {
        $class = SchoolContext::forUser($request->user());
        $students = $class
            ? User::where('role', 'siswa')
                ->whereHas('classMembers', fn ($query) => $query->where('class_id', $class->id)->where('is_active', true))
                ->orderBy('name')
                ->get()
            : collect();
        $treasurers = $class ? $this->availableTreasurers($class) : collect();
        $currentTreasurer = $class?->treasurer;
        $rosterCount = $students->count() + ($currentTreasurer ? 1 : 0);

        return view('walikelas.class', compact('class', 'students', 'treasurers', 'currentTreasurer', 'rosterCount'));
    }

    public function createAccount(Request $request)
    {
        $class = $this->classFor($request);

        return view('walikelas.accounts.create', compact('class'));
    }

    public function storeAccount(Request $request)
    {
        $class = $this->classFor($request);
        $data = $request->validate([
            'account_type' => ['required', Rule::in(['siswa', 'bendahara'])],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        DB::transaction(function () use ($data, $class) {
            $lockedClass = ClassModel::whereKey($class->id)->lockForUpdate()->firstOrFail();
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'role' => $data['account_type'],
            ]);

            if ($data['account_type'] === 'bendahara') {
                $this->setTreasurer($lockedClass, $user);

                return;
            }

            ClassMember::create([
                'class_id' => $lockedClass->id,
                'user_id' => $user->id,
                'role_in_class' => 'anggota',
                'is_active' => true,
            ]);
        });

        $message = $data['account_type'] === 'bendahara'
            ? 'Akun bendahara dibuat dan ditugaskan ke kelas.'
            : 'Akun siswa dibuat dan langsung ditambahkan ke kelas.';

        return redirect()->route('walikelas.class')->with('status', $message);
    }

    public function assignTreasurer(Request $request)
    {
        $class = $this->classFor($request);
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'bendahara')],
        ]);
        $treasurer = User::where('role', 'bendahara')->findOrFail($data['user_id']);

        DB::transaction(fn () => $this->setTreasurer($class, $treasurer));

        return redirect()->route('walikelas.class')->with('status', 'Bendahara kelas berhasil diperbarui.');
    }

    private function classFor(Request $request): ClassModel
    {
        return SchoolContext::forUser($request->user())
            ?? throw ValidationException::withMessages(['class' => 'Kelas belum tersedia pada akun wali kelas ini.']);
    }

    private function setTreasurer(ClassModel $class, User $treasurer): void
    {
        $class = ClassModel::whereKey($class->id)->lockForUpdate()->firstOrFail();
        $ids = collect([$class->treasurer_id, $treasurer->id])->filter()->unique()->sort()->all();
        User::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();

        if (ClassModel::where('is_active', true)->where('treasurer_id', $treasurer->id)->where('id', '!=', $class->id)->exists()) {
            throw ValidationException::withMessages(['user_id' => 'Bendahara tersebut sudah ditugaskan pada kelas lain.']);
        }

        if (ClassMember::where('user_id', $treasurer->id)->where('is_active', true)->where('class_id', '!=', $class->id)->exists()) {
            throw ValidationException::withMessages(['user_id' => 'Akun tersebut masih terdaftar pada kelas lain.']);
        }

        if ($class->treasurer_id && $class->treasurer_id !== $treasurer->id) {
            ClassMember::where('class_id', $class->id)
                ->where('user_id', $class->treasurer_id)
                ->where('role_in_class', 'bendahara')
                ->update(['role_in_class' => 'anggota']);
        }

        $class->update(['treasurer_id' => $treasurer->id]);
        ClassMember::updateOrCreate(
            ['class_id' => $class->id, 'user_id' => $treasurer->id],
            ['role_in_class' => 'bendahara', 'is_active' => true]
        );
    }

    private function availableTreasurers(ClassModel $class)
    {
        $assignedElsewhere = ClassModel::query()
            ->where('is_active', true)
            ->where('id', '!=', $class->id)
            ->whereNotNull('treasurer_id')
            ->select('treasurer_id');

        return User::where('role', 'bendahara')
            ->where(function ($query) use ($class, $assignedElsewhere) {
                $query->where(function ($available) use ($class, $assignedElsewhere) {
                    $available->whereNotIn('id', $assignedElsewhere)
                        ->whereDoesntHave('classMembers', fn ($members) => $members
                            ->where('is_active', true)
                            ->where('class_id', '!=', $class->id));
                });
                if ($class->treasurer_id) {
                    $query->orWhere('id', $class->treasurer_id);
                }
            })
            ->orderBy('name')
            ->get();
    }
}
