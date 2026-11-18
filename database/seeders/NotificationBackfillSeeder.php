<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\FinanceNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationBackfillSeeder extends Seeder
{
    /**
     * Samakan URL notifikasi lama dan pastikan setiap peran menerima notifikasi.
     * Aman diulang.
     */
    public function run(): void
    {
        foreach (DB::table('notifications')->select('id', 'data')->get() as $row) {
            $data = json_decode((string) $row->data, true);
            if (! is_array($data) || empty($data['url'])) {
                continue;
            }

            $path = parse_url($data['url'], PHP_URL_PATH);
            if (! is_string($path) || $path === '' || $path === $data['url']) {
                continue;
            }

            $data['url'] = $path;
            DB::table('notifications')->where('id', $row->id)->update(['data' => json_encode($data)]);
        }

        foreach (User::orderBy('id')->get() as $user) {
            if ($user->notifications()->exists()) {
                continue;
            }

            [$title, $message, $url] = match ($user->role) {
                'wali_kelas' => ['Tagihan kelas tersedia', 'Tagihan kas kelas telah diterbitkan untuk siswa.', '/walikelas/reports'],
                'bendahara' => ['Tagihan kas dibuat', 'Tagihan Kas Kelas 2026/2027 telah dibuat untuk siswa.', '/class-fees'],
                default => ['Pembayaran kas tercatat', 'Pembayaran kas kelas Anda telah dicatat.', '/student/transactions'],
            };

            $user->notify(new FinanceNotification(compact('title', 'message', 'url')));
        }

        foreach (User::where('role', 'wali_kelas')->get() as $user) {
            $titles = $user->notifications()->pluck('data')->map(
                fn ($data) => is_array($data) ? ($data['title'] ?? '') : '',
            )->all();

            if (! in_array('Ringkasan pembayaran kelas', $titles, true)) {
                $user->notify(new FinanceNotification([
                    'title' => 'Ringkasan pembayaran kelas',
                    'message' => 'Pembayaran siswa terdiri dari cash dan transfer bank/e-wallet. Transfer yang menunggu verifikasi dapat dicek oleh bendahara.',
                    'url' => '/walikelas/reports',
                ]));
            }
        }
    }
}
