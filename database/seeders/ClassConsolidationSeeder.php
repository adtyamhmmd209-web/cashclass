<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassConsolidationSeeder extends Seeder
{
    private const TARGET_CLASS = 'X PPLG 1';

    private const LEGACY_CLASS = 'XII RPL 4';

    private const CLASS_SCOPED_TABLES = [
        'announcements',
        'categories',
        'class_fee_assignments',
        'class_fees',
        'lesson_schedules',
        'payment_accounts',
        'payment_submissions',
        'student_attendances',
        'student_grades',
        'transactions',
    ];

    /**
     * Gabungkan kelas lama ke X PPLG 1: pindahkan 17 siswa beserta seluruh
     * data terkait kelas, lalu hapus kelas lama. Aman diulang.
     */
    public function run(): void
    {
        $target = ClassModel::where('name', self::TARGET_CLASS)->orderBy('id')->first();
        if (! $target) {
            return;
        }

        $legacy = ClassModel::where('name', self::LEGACY_CLASS)->orderBy('id')->first();

        if ($legacy && $legacy->id !== $target->id) {
            DB::transaction(function () use ($target, $legacy) {
                foreach (DB::table('class_members')->where('class_id', $legacy->id)->get() as $member) {
                    $alreadyJoined = DB::table('class_members')
                        ->where('class_id', $target->id)
                        ->where('user_id', $member->user_id)
                        ->exists();

                    if ($alreadyJoined) {
                        DB::table('class_members')->where('id', $member->id)->delete();
                    } else {
                        DB::table('class_members')->where('id', $member->id)->update(['class_id' => $target->id]);
                    }
                }

                foreach (self::CLASS_SCOPED_TABLES as $table) {
                    DB::table($table)->where('class_id', $legacy->id)->update(['class_id' => $target->id]);
                }

                DB::table('classes')->where('id', $legacy->id)->delete();
            });
        }

        $income = DB::table('transactions')->where('class_id', $target->id)->where('type', 'income')->sum('amount');
        $expense = DB::table('transactions')->where('class_id', $target->id)->where('type', 'expense')->sum('amount');
        DB::table('classes')->where('id', $target->id)->update(['current_balance' => $income - $expense]);
    }
}
