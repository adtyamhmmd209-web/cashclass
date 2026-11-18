<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class NisnNormalizationSeeder extends Seeder
{
    /**
     * Pastikan setiap akun memiliki NISN tepat 10 digit angka dan unik.
     * Aman diulang: akun yang sudah valid tidak diubah.
     */
    public function run(): void
    {
        $used = User::query()
            ->whereNotNull('nisn')
            ->pluck('nisn')
            ->map(fn ($nisn) => (string) $nisn)
            ->flip()
            ->all();

        $counter = 7000000001;

        foreach (User::query()->orderBy('id')->get() as $user) {
            $nisn = $user->nisn === null ? null : (string) $user->nisn;

            if ($nisn !== null && preg_match('/^[0-9]{10}$/', $nisn) && ! isset($used[$nisn])) {
                $used[$nisn] = true;

                continue;
            }

            do {
                $candidate = (string) $counter++;
            } while (isset($used[$candidate]));

            $user->update(['nisn' => $candidate]);
            $used[$candidate] = true;
        }
    }
}
