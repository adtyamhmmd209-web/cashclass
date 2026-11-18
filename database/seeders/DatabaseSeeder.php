<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Bendahara
        $bendahara = User::create([
            'username' => 'bendahara',
            'nisn' => '7000000011',
            'name' => 'Siti Bendahara',
            'email' => 'bendahara@cashclass.com',
            'password' => Hash::make('bendahara123'),
            'role' => 'bendahara',
            'phone' => '081234567891',
        ]);

        // 2. Buat Wali Kelas
        $walikelas = User::create([
            'username' => 'walikelas',
            'nisn' => '7000000012',
            'name' => 'Pak Budi Santoso',
            'email' => 'walikelas@cashclass.com',
            'password' => Hash::make('walikelas123'),
            'role' => 'wali_kelas',
            'phone' => '081234567890',
        ]);

        // 3. Buat Siswa
        $siswa1 = User::create([
            'username' => 'andi',
            'nisn' => '7000000013',
            'name' => 'Andi Saputra',
            'email' => 'andi@cashclass.com',
            'password' => Hash::make('andi123'),
            'role' => 'siswa',
            'phone' => '081234567892',
        ]);

        $siswa2 = User::create([
            'username' => 'siti',
            'nisn' => '7000000014',
            'name' => 'Siti Aisyah',
            'email' => 'siti@cashclass.com',
            'password' => Hash::make('siti123'),
            'role' => 'siswa',
            'phone' => '081234567894',
        ]);

        // 4. Buat Kelas
        $classId = DB::table('classes')->insertGetId([
            'name' => 'X PPLG 1',
            'description' => 'Kelas X PPLG 1',
            'academic_year' => '2026/2027',
            'teacher_id' => $walikelas->id,
            'current_balance' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Tambahkan Anggota ke Kelas
        DB::table('class_members')->insert([
            ['class_id' => $classId, 'user_id' => $bendahara->id, 'role_in_class' => 'bendahara', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['class_id' => $classId, 'user_id' => $walikelas->id, 'role_in_class' => 'ketua', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['class_id' => $classId, 'user_id' => $siswa1->id, 'role_in_class' => 'anggota', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['class_id' => $classId, 'user_id' => $siswa2->id, 'role_in_class' => 'anggota', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 6. Buat Kategori
        $categories = [
            ['name' => 'Iuran Bulanan', 'type' => 'income', 'color' => '#10b981', 'class_id' => $classId],
            ['name' => 'Donasi', 'type' => 'income', 'color' => '#3b82f6', 'class_id' => $classId],
            ['name' => 'Konsumsi', 'type' => 'expense', 'color' => '#f59e0b', 'class_id' => $classId],
            ['name' => 'Kebersihan', 'type' => 'expense', 'color' => '#ef4444', 'class_id' => $classId],
            ['name' => 'Kegiatan Kelas', 'type' => 'expense', 'color' => '#8b5cf6', 'class_id' => $classId],
            ['name' => 'Lain-lain', 'type' => 'expense', 'color' => '#6b7280', 'class_id' => $classId],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert(array_merge($category, ['created_at' => now(), 'updated_at' => now()]));
        }

        $this->call(ClassConsolidationSeeder::class);
        $this->call(StudentBulkSeeder::class);
        $this->call(NisnNormalizationSeeder::class);
        $this->call(AccountCredentialsSeeder::class);
        $this->call(PaymentBulkSeeder::class);
        $this->call(NotificationBackfillSeeder::class);
    }
}