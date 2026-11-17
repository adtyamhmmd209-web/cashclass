<?php

namespace Tests\Concerns;

use App\Models\ClassMember;
use App\Models\ClassModel;
use App\Models\PaymentAccount;
use App\Models\User;
use Illuminate\Http\UploadedFile;

trait CreatesCashClass
{
    protected function createCashClass(string $name = 'XI RPL'): array
    {
        $treasurer = User::factory()->create(['role' => 'bendahara']);
        $teacher = User::factory()->create(['role' => 'wali_kelas']);
        $student = User::factory()->create(['role' => 'siswa']);
        $class = ClassModel::create(['name' => $name, 'academic_year' => '2026/2027', 'treasurer_id' => $treasurer->id, 'teacher_id' => $teacher->id]);
        ClassMember::create(['class_id' => $class->id, 'user_id' => $treasurer->id, 'role_in_class' => 'bendahara']);
        ClassMember::create(['class_id' => $class->id, 'user_id' => $student->id, 'role_in_class' => 'anggota']);
        $bank = PaymentAccount::create(['class_id' => $class->id, 'type' => 'bank', 'provider' => 'BANK KELAS', 'account_number' => '1234567890', 'account_holder' => 'Kas '.$name]);
        $wallet = PaymentAccount::create(['class_id' => $class->id, 'type' => 'e_wallet', 'provider' => 'DOMPET KELAS', 'account_number' => '081234567890', 'account_holder' => 'Kas '.$name]);

        return compact('treasurer', 'teacher', 'student', 'class', 'bank', 'wallet');
    }

    protected function transactionData(array $replace = []): array
    {
        return array_replace(['date' => now()->toDateString(), 'type' => 'income', 'category' => 'Kas', 'description' => 'Kas kelas', 'amount' => 15000], $replace);
    }

    protected function proofFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('bukti.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jZQAAAABJRU5ErkJggg=='));
    }
}
