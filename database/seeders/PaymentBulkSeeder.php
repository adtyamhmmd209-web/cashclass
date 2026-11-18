<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ClassFee;
use App\Models\ClassFeeAssignment;
use App\Models\ClassModel;
use App\Models\PaymentAccount;
use App\Models\PaymentSubmission;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\PaymentNotification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentBulkSeeder extends Seeder
{
    private const FEE_TITLE = 'Kas Kelas 2026/2027';

    private const AMOUNT = 25000;

    private const PROOF_PATH = 'payment-proofs/6fXQXvFDcNI2pA4I5OmNnJ2WUwXC9ziCbWd4jMbG.png';

    /**
     * Tagihan + pembayaran untuk seluruh siswa X PPLG 1 dengan metode
     * campuran (cash approved, transfer approved, transfer pending).
     * Aman diulang: bila data metode campuran sudah ada, seeder tidak
     * membangun ulang atau mengirim notifikasi duplikat.
     */
    public function run(): void
    {
        $class = ClassModel::where('name', 'X PPLG 1')->orderBy('id')->first();
        if (! $class) {
            return;
        }

        $hasMixedMethods = PaymentSubmission::withoutGlobalScopes()
            ->where('class_id', $class->id)
            ->whereIn('method', ['bank', 'e_wallet'])
            ->exists();
        if ($hasMixedMethods && PaymentAccount::withoutGlobalScopes()->where('class_id', $class->id)->exists()) {
            $this->recalculateBalance($class->id);

            return;
        }

        $reviewer = User::where('role', 'bendahara')->where('id', $class->treasurer_id)->first()
            ?? User::where('role', 'bendahara')->orderBy('id')->first();
        if (! $reviewer) {
            return;
        }

        $students = User::where('role', 'siswa')
            ->whereHas('classMembers', fn ($query) => $query->where('class_id', $class->id)->where('is_active', true))
            ->orderBy('id')
            ->get();
        if ($students->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($class, $reviewer, $students) {
            $linkedTransactionIds = PaymentSubmission::withoutGlobalScopes()
                ->where('class_id', $class->id)
                ->pluck('transaction_id')
                ->filter()
                ->all();

            PaymentSubmission::withoutGlobalScopes()->where('class_id', $class->id)->delete();
            if ($linkedTransactionIds !== []) {
                Transaction::withoutGlobalScopes()->whereIn('id', $linkedTransactionIds)->delete();
            }
            ClassFeeAssignment::withoutGlobalScopes()->where('class_id', $class->id)->delete();
            ClassFee::withoutGlobalScopes()->where('class_id', $class->id)->delete();

            $category = Category::firstOrCreate(
                ['class_id' => $class->id, 'name' => 'Pembayaran Kas', 'type' => 'income'],
                ['color' => '#10b981'],
            );

            $bank = PaymentAccount::withoutGlobalScopes()->firstOrCreate(
                ['class_id' => $class->id, 'type' => 'bank', 'provider' => 'BCA'],
                ['account_number' => '1234567890', 'account_holder' => strtoupper($reviewer->name), 'is_active' => true],
            );
            $eWallet = PaymentAccount::withoutGlobalScopes()->firstOrCreate(
                ['class_id' => $class->id, 'type' => 'e_wallet', 'provider' => 'DANA'],
                ['account_number' => $reviewer->phone ?: '081234567891', 'account_holder' => strtoupper($reviewer->name), 'is_active' => true],
            );

            $fee = ClassFee::withoutGlobalScopes()->create([
                'class_id' => $class->id,
                'created_by' => $reviewer->id,
                'title' => self::FEE_TITLE,
                'amount' => self::AMOUNT,
                'starts_at' => '2026-10-01',
                'due_date' => '2026-11-30',
                'description' => 'Tagihan kas kelas tahun ajaran 2026/2027.',
            ]);

            $startDate = Carbon::parse('2026-10-18');
            $pendingStudents = [];

            foreach ($students as $index => $student) {
                $assignment = ClassFeeAssignment::withoutGlobalScopes()->create([
                    'class_fee_id' => $fee->id,
                    'class_id' => $class->id,
                    'user_id' => $student->id,
                ]);

                $bucket = $index % max($students->count(), 1);
                $method = $bucket < 12 ? 'cash' : ($bucket % 2 === 0 ? 'bank' : 'e_wallet');
                $pending = $bucket >= 24;
                $paymentDate = $pending
                    ? Carbon::parse('2026-11-15')->addDays($bucket % 4)
                    : $startDate->copy()->addDays($bucket % 32);

                $attributes = [
                    'class_id' => $class->id,
                    'user_id' => $student->id,
                    'class_fee_assignment_id' => $assignment->id,
                    'payment_date' => $paymentDate->toDateString(),
                    'amount' => self::AMOUNT,
                    'description' => 'Pembayaran kas - '.$student->name,
                    'status' => $pending ? 'pending' : 'approved',
                    'reviewed_by' => $pending ? null : $reviewer->id,
                    'submission_key' => $this->submissionKey($student->username),
                ];

                if ($method === 'cash') {
                    $attributes['method'] = 'cash';
                    $attributes['cash_received'] = self::AMOUNT;
                    $attributes['cash_change'] = 0;
                } else {
                    $account = $method === 'bank' ? $bank : $eWallet;
                    $attributes['method'] = $method;
                    $attributes['payment_account_id'] = $account->id;
                    $attributes['payment_provider'] = $account->provider;
                    $attributes['destination_number'] = $account->account_number;
                    $attributes['destination_holder'] = $account->account_holder;
                    $attributes['proof_image'] = self::PROOF_PATH;
                }

                if (! $pending) {
                    $description = 'Pembayaran kas - '.$student->name;
                    $transaction = Transaction::withoutGlobalScopes()->create([
                        'class_id' => $class->id,
                        'user_id' => $reviewer->id,
                        'category_id' => $category->id,
                        'type' => 'income',
                        'amount' => self::AMOUNT,
                        'title' => $description,
                        'description' => $description,
                        'transaction_date' => $paymentDate->toDateString(),
                    ]);
                    $attributes['transaction_id'] = $transaction->id;
                }

                PaymentSubmission::withoutGlobalScopes()->create($attributes);

                if ($pending) {
                    $pendingStudents[] = $student;
                }

                $student->notify(new PaymentNotification([
                    'title' => match (true) {
                        $method === 'cash' => 'Pembayaran cash dicatat',
                        $pending => 'Transfer menunggu verifikasi',
                        default => 'Pembayaran transfer disetujui',
                    },
                    'message' => $pending
                        ? 'Bukti transfer Rp '.number_format(self::AMOUNT, 0, ',', '.').' sedang diverifikasi bendahara.'
                        : 'Pembayaran Rp '.number_format(self::AMOUNT, 0, ',', '.').' via '.$this->methodLabel($method).' telah masuk ke kas kelas.',
                    'url' => route('student.transactions'),
                ]));
            }

            if ($pendingStudents !== []) {
                $reviewer->notify(new PaymentNotification([
                    'title' => count($pendingStudents).' transfer menunggu verifikasi',
                    'message' => 'Ada '.count($pendingStudents).' pembayaran transfer bank/e-wallet yang perlu Anda periksa dan setujui.',
                    'url' => route('payments.index'),
                ]));
            }
        });

        $this->recalculateBalance($class->id);
    }

    private function methodLabel(string $method): string
    {
        return match ($method) {
            'bank' => 'transfer bank', 'e_wallet' => 'e-wallet', default => 'cash',
        };
    }

    private function submissionKey(string $username): string
    {
        $hash = md5('cashclass-payment-v2-'.$username);

        return substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-4'.substr($hash, 13, 3)
            .'-a'.substr($hash, 17, 3).'-'.substr($hash, 20, 12);
    }

    private function recalculateBalance(int $classId): void
    {
        $income = Transaction::withoutGlobalScopes()->where('class_id', $classId)->ofType('income')->sum('amount');
        $expense = Transaction::withoutGlobalScopes()->where('class_id', $classId)->ofType('expense')->sum('amount');
        ClassModel::whereKey($classId)->update(['current_balance' => $income - $expense]);
    }
}
