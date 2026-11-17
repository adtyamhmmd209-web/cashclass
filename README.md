# CashClass — Sistem Informasi Kas Kelas

## 1. Deskripsi Proyek

**CashClass** adalah aplikasi web untuk mengelola keuangan dan kebutuhan akademik dasar kelas secara terstruktur. Aplikasi menyediakan akses untuk **Wali Kelas**, **Bendahara**, dan **Siswa**, dengan data yang dikelola sesuai kelas masing-masing.

Wali Kelas mengelola akun siswa dan penugasan Bendahara. Bendahara mencatat transaksi, membuat tagihan, serta memverifikasi pembayaran. Siswa dapat melihat tagihan dan riwayat pembayaran, mengirim bukti transfer, serta melihat pengumuman dan jadwal kelas.

## 2. Fitur Utama

- **Akses multi-role:** Halaman dan tindakan dibatasi berdasarkan peran Wali Kelas, Bendahara, atau Siswa.
- **Manajemen kelas dan akun:** Wali Kelas mengelola data siswa serta penugasan Bendahara.
- **Pencatatan transaksi:** Bendahara mencatat pemasukan dan pengeluaran, termasuk bukti transaksi.
- **Tagihan kelas:** Bendahara membuat tagihan untuk periode tertentu dengan nominal, tanggal mulai, tenggat, dan daftar penerima.
- **Pembayaran dan verifikasi:** Siswa mengirim pembayaran melalui transfer dengan bukti, atau membayar tunai kepada Bendahara. Bendahara dapat meninjau pembayaran transfer.
- **Ringkasan pembayaran:** Dashboard Wali Kelas menampilkan ringkasan pembayaran dan tunggakan siswa.
- **Pengumuman dan jadwal:** Wali Kelas dapat mengelola pengumuman; siswa dapat melihat pengumuman dan jadwal.
- **Laporan keuangan:** Laporan transaksi dapat difilter berdasarkan rentang tanggal dan dicetak ke PDF.
- **API transaksi:** Tersedia autentikasi token menggunakan Laravel Sanctum.

Tagihan tidak dibuat berulang secara otomatis; Bendahara membuat tagihan baru saat diperlukan. Transaksi kas umum yang tidak terkait tagihan tetap dapat dicatat.

## 3. Teknologi yang Digunakan

| Layer | Teknologi |
| ----- | ----- |
| **Bahasa / Framework** | PHP 8.2+, Laravel 12 |
| **Database** | SQLite (pengembangan lokal), MySQL juga dapat digunakan |
| **Frontend / View** | Blade, Tailwind CSS, Alpine.js |
| **Asset bundler** | Vite 7 |
| **Autentikasi API** | Laravel Sanctum |

## 4. Persyaratan Sistem

Sebelum menjalankan aplikasi, siapkan:

- **PHP 8.2 atau lebih baru** dengan ekstensi yang dibutuhkan Laravel, termasuk PDO SQLite untuk konfigurasi bawaan.
- **Composer**
- **Node.js dan npm**
- **SQLite** (atau MySQL jika konfigurasi database diubah)
- Browser web modern

## 5. Panduan Instalasi

### 1. Instal dependensi

Jalankan dari direktori proyek:

```sh
composer install
npm install
```

### 2. Siapkan environment dan database

Di PowerShell, salin konfigurasi contoh dan buat application key:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Konfigurasi bawaan menggunakan SQLite. Untuk memakai MySQL, sesuaikan variabel `DB_*` di file `.env`.

Jalankan migrasi dan seeder untuk membuat tabel serta akun demo:

```sh
php artisan migrate --seed
```

### 3. Jalankan aplikasi

```sh
composer run dev
```

Perintah tersebut menjalankan server Laravel dan Vite untuk pengembangan. Buka alamat lokal yang ditampilkan Artisan, biasanya `http://127.0.0.1:8000`.

Pendaftaran publik tersedia untuk Wali Kelas. Saat mendaftar, Wali Kelas membuat akun sekaligus nama kelas dan tahun ajaran. Setelah masuk, Wali Kelas dapat mengelola akun siswa dan Bendahara melalui halaman data kelas.

## 6. Akun Demo

Akun berikut dibuat oleh seeder untuk pengujian lokal setelah menjalankan `php artisan migrate --seed`:

| Peran | Email | Password |
| ----- | ----- | ----- |
| **Bendahara** | `bendahara@cashclass.com` | `bendahara123` |
| **Wali Kelas** | `walikelas@cashclass.com` | `walikelas123` |
| **Siswa** | `andi@cashclass.com` | `andi123` |
| **Siswa** | `siti@cashclass.com` | `siti123` |

> Akun tersebut hanya untuk pengujian lokal. Jangan gunakan kredensial demo untuk deployment production.

## 7. Tim Pengembang

- **Backend Engineer & Ketua:** **Adi Permana** — Mengembangkan backend dan mengoordinasikan tim.
- **Frontend Engineer:** **Aditya Muhammad Ramdani Irawan** — Mengembangkan antarmuka dan pengalaman pengguna aplikasi.

## Pengujian

Jalankan pengujian dengan:

```sh
php artisan test
```
