<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccountCredentialsSeeder extends Seeder
{
    /**
     * Pastikan semua peran bisa login di lingkungan deployment dengan
     * kredensial yang sudah diketahui. Aman diulang: hash hanya diubah
     * jika password berbeda.
     */
    private const PASSWORDS = [
        'bendahara' => 'bendahara123',
        'wali_kelas' => 'walikelas123',
        'siswa' => 'siswa123',
    ];

    public function run(): void
    {
        foreach (self::PASSWORDS as $role => $password) {
            User::where('role', $role)->each(function (User $user) use ($password) {
                if (! Hash::check($password, $user->password)) {
                    $user->update(['password' => Hash::make($password)]);
                }
            });
        }
    }
}
