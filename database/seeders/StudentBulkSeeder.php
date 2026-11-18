<?php

namespace Database\Seeders;

use App\Models\ClassMember;
use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentBulkSeeder extends Seeder
{
    /**
     * 34 akun siswa dengan nama dan NISN acak (nilai tetap agar seed aman diulang).
     */
    private array $students = [
        ['Aisyah Putri Ramadhani', '3780849302'],
        ['Bima Pratama', '6598910856'],
        ['Citra Maharani', '2525736933'],
        ['Daffa Ramadhan', '6709325311'],
        ['Nadia Safitri', '9354622897'],
        ['Eka Saputra', '9821671393'],
        ['Fajar Nugroho', '4675070699'],
        ['Gita Prameswari', '9501127683'],
        ['Hafiz Akbar', '9968782983'],
        ['Intan Permata', '3704524270'],
        ['Joko Susilo', '3004131744'],
        ['Kirana Aulia', '1853193467'],
        ['Lala Amelia', '9185024732'],
        ['M. Rizky Pratama', '1694618298'],
        ['Nabila Zahra', '6806121591'],
        ['Oki Setiawan', '8330581263'],
        ['Putri Ayu Lestari', '7692431633'],
        ['Qorry Ananda', '1340297048'],
        ['Rafi Alfarizi', '1210409159'],
        ['Salma Nabila', '9221064824'],
        ['Tegar Pamungkas', '8856703983'],
        ['Umairah Hamidah', '9439174620'],
        ['Vino Ramadhan', '5549964521'],
        ['Wulan Sari', '6979068560'],
        ['Xaviera Putri', '6650743797'],
        ['Yuda Mahendra', '8038281146'],
        ['Zalfa Anindya', '6443138116'],
        ['Aditya Prakoso', '3478594611'],
        ['Bella Aprilia', '7729633020'],
        ['Choirul Anam', '9390164471'],
        ['Dinda Ayu Lestari', '3949755623'],
        ['Farhan Maulana', '1834551025'],
        ['Gina Kartika', '6209342415'],
        ['Hendra Gunawan', '9640401707'],
    ];

    public function run(): void
    {
        $classIds = ClassModel::where('is_active', true)->orderBy('id')->pluck('id');

        foreach ($this->students as $index => [$name, $nisn]) {
            $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $username = 'murid'.$number;
            $email = 'murid'.$number.'@cashclass.com';

            $student = User::where('username', $username)
                ->orWhere('email', $email)
                ->orWhere('nisn', $nisn)
                ->first();

            if (! $student) {
                $student = User::create([
                    'username' => $username,
                    'nisn' => $nisn,
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('siswa123'),
                    'role' => 'siswa',
                    'phone' => '0812'.str_pad((string) (10000000 + $index), 8, '0', STR_PAD_LEFT),
                    'email_verified_at' => now(),
                ]);
            }

            if ($classIds->isNotEmpty()) {
                $classId = $classIds[$index % $classIds->count()];
                ClassMember::firstOrCreate(
                    ['class_id' => $classId, 'user_id' => $student->id],
                    ['role_in_class' => 'anggota', 'is_active' => true],
                );
            }
        }
    }
}
