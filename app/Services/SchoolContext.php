<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SchoolContext
{
    public static function forUser(User $user): ?ClassModel
    {
        $classes = ClassModel::where('is_active', true)->orderBy('id');
        if ($user->isBendahara()) {
            return $classes->where('treasurer_id', $user->id)->first();
        }
        if ($user->isWaliKelas()) {
            return $classes->where('teacher_id', $user->id)->first();
        }

        return $classes->whereHas('members', fn ($q) => $q->where('user_id', $user->id)->where('is_active', true))->first();
    }

    public static function requireForUser(User $user): ClassModel
    {
        $class = self::forUser($user);
        if (! $class || ! $class->treasurer_id || ! $class->teacher_id) {
            throw ValidationException::withMessages(['class' => 'Wali kelas harus menetapkan satu bendahara untuk mengaktifkan fitur keuangan kelas.']);
        }

        return $class;
    }
}
