<?php

namespace App\Services;

use App\Models\ClassFee;
use App\Models\ClassModel;
use App\Models\User;
use App\Notifications\FinanceNotification;

class ClassNotifier
{
    public function waliKelas(ClassModel $class, string $title, string $message, string $url): void
    {
        User::where('role', 'wali_kelas')->whereKey($class->teacher_id)->first()
            ?->notify(new FinanceNotification(compact('title', 'message', 'url')));
    }

    public function bendahara(ClassModel $class, string $title, string $message, string $url): void
    {
        User::where('role', 'bendahara')->whereKey($class->treasurer_id)->first()
            ?->notify(new FinanceNotification(compact('title', 'message', 'url')));
    }

    public function students(ClassFee $fee, string $title, string $message, string $url): void
    {
        $fee->assignments()->with('user')->get()
            ->pluck('user')
            ->filter()
            ->each(fn (User $student) => $student->notify(new FinanceNotification(compact('title', 'message', 'url'))));
    }
}
