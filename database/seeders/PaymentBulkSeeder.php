<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ClassFee;
use App\Models\ClassFeeAssignment;
use App\Models\ClassModel;
use App\Models\PaymentSubmission;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PaymentBulkSeeder extends Seeder
{
    private const FEE_TITLE = 'Kas Kelas 2026/2027';
    private const AMOUNT = 25000;

    /**
     * Tagihan + pembayaran tunai approved untuk seluruh siswa, lengkap dengan
     * transaksi kas terkait. Aman diulang: setiap siswa hanya diproses sekali.
     */
    public function run(): void
    {
        $reviewer = User::where('role', 'bendahara')->orderBy('id')->first();
        if (! $reviewer) {
            return;
        }

        $startDate = Carbon::parse('2026-10-18');
        $day = 0;

        foreach (ClassModel::where('is_active', true)->orderBy('id')->get() as $class) {
            $fee = ClassFee::firstOrCreate(
                ['class_id' => $class->id, 'title' => self::FEE_TITLE],
                [
                    'created_by' => $reviewer->id,
                    'amount' => self::AMOUNT,
                    'starts_at' => '2026-10-01',
                    'due_date' => '2026-11-30',
                    'description' => 'Tagihan kas kelas tahun ajaran 2026/2027.',
                ],
            );

            $category = Category::firstOrCreate(
                ['class_id' => $class->id, 'name' => 'Pembayaran Kas', 'type' => 'income'],
                ['color' => '#10b981'],
            );

            $students = User::where('role', 'siswa')
                ->whereHas('classMembers', fn ($q) => $q->where('class_id', $class->id)->where('is_active', true))
                ->orderBy('id')
                ->get();

            foreach ($students as $student) {
                $assignment = ClassFeeAssignment::firstOrCreate([
                    'class_fee_id' => $fee->id,
                    'class_id' => $class->id,
                    'user_id' => $student->id,
                ]);

                $paid = PaymentSubmission::withoutGlobalScopes()
                    ->where('class_fee_assignment_id', $assignment->id)
                    ->where('status', 'approved')
                    ->exists();
                if ($paid) {
                    continue;
                }

                $paymentDate = $startDate->copy()->addDays($day % 32);
                if ($paymentDate->isFuture()) {
                    $paymentDate = today();
                }
                $day++;

                $description = 'Pembayaran kas - '.$student->name;

                $transaction = Transaction::create([
                    'class_id' => $class->id,
                    'user_id' => $reviewer->id,
                    'category_id' => $category->id,
                    'type' => 'income',
                    'amount' => self::AMOUNT,
                    'title' => $description,
                    'description' => $description,
                    'transaction_date' => $paymentDate->toDateString(),
                ]);

                PaymentSubmission::create([
                    'class_id' => $class->id,
                    'user_id' => $student->id,
                    'class_fee_assignment_id' => $assignment->id,
                    'payment_date' => $paymentDate->toDateString(),
                    'amount' => self::AMOUNT,
                    'description' => $description,
                    'method' => 'cash',
                    'status' => 'approved',
                    'reviewed_by' => $reviewer->id,
                    'transaction_id' => $transaction->id,
                    'submission_key' => $this->submissionKey($student),
                    'cash_received' => self::AMOUNT,
                    'cash_change' => 0,
                ]);
            }

            $income = Transaction::where('class_id', $class->id)->ofType('income')->sum('amount');
            $expense = Transaction::where('class_id', $class->id)->ofType('expense')->sum('amount');
            $class->update(['current_balance' => $income - $expense]);
        }
    }

    private function submissionKey(User $student): string
    {
        $hash = md5('cashclass-payment-'.$student->username);

        return substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-4'.substr($hash, 13, 3)
            .'-a'.substr($hash, 17, 3).'-'.substr($hash, 20, 12);
    }
}
