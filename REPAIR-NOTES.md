# CashClass — pembayaran dan tampilan

## Pembayaran

- Bendahara mengisi tujuan bank/e-wallet di Pengaturan kelas: penyedia, nomor, dan nama pemilik. Nomor yang sama disimpan sekali per kelas dan penyedia. Tujuan bisa dinonaktifkan tanpa mengubah riwayat transfer sebelumnya.
- Siswa memilih metode dan tujuan yang aktif milik kelasnya, melakukan transfer, lalu wajib mengunggah bukti gambar maksimal 2 MB. Nomor serta nama tujuan disalin ke riwayat pembayaran agar tidak ikut berubah ketika tujuan diedit.
- Transfer masuk berstatus menunggu verifikasi. Persetujuan bendahara menambah pemasukan dalam satu transaksi database; penolakan tidak mengubah saldo. Transfer ganda dengan kunci kiriman yang sama ditolak.
- Cash dicatat bendahara lewat Pembayaran siswa, dengan nama siswa, tanggal, nominal dan keterangan. Pencatatan langsung membuat pembayaran disetujui dan pemasukan kas. Pengiriman ulang formulir yang sama tidak menggandakan pemasukan.
- Transfer menggunakan unggah bukti dan pemeriksaan bendahara. Tidak ada payment gateway atau konfirmasi otomatis dari bank/e-wallet.

## Pembagian kelas

- Setiap kelas mempunyai satu kolom bendahara dan satu kolom wali kelas sebagai penanggung jawab. Formulir pengaturan wajib memilih wali kelas; bendahara terikat ke akun yang menyimpan kelas.
- Wali kelas yang sudah bertugas pada kelas aktif lain tidak dapat dipilih lagi. Akun wali kelas baru dapat dibuat dari pengaturan bila belum tersedia.
- Kelas lama dipasangkan ke bendahara dari keanggotaan bendahara yang sudah ada. Pembagian sebelumnya dicadangkan sebelum migrasi. Akun tanpa penugasan tidak otomatis mendapat akses ke kelas pertama.
- Transaksi, saldo, tujuan pembayaran, bukti pembayaran, daftar siswa, pengumuman dan jadwal dibatasi ke kelas akun, termasuk akses API dan akses melalui ID di URL.

## Tampilan dan navigasi

- Seluruh halaman masuk aplikasi menggunakan satu sidebar bersama, tanpa menu atas atau menu footer yang berulang. Profil dan keamanan hanya memiliki satu halaman. URL profil/pengaturan lama dialihkan ke halaman bersama.
- Dashboard siswa berisi ringkasan saldo, pembayaran milik sendiri, dan pengumuman; halaman riwayat menampilkan rincian pembayaran serta buku kas.
- Tombol memiliki padding, warna, border, hover dan fokus yang konsisten. Sidebar ponsel memakai overlay, tombol tutup, Escape dan pembatasan fokus keyboard.
- Chart.js serta stylesheet aplikasi dimuat dari aset lokal.
- Fitur nilai siswa dihapus dari rute, controller, sidebar dan template aktif. File sumber lama dipindahkan ke cadangan; tabel nilai historis tidak dihapus. Laporan wali kelas sekarang berisi laporan kas.

## Pengujian dan cadangan

Tes memakai SQLite dalam memori, terpisah dari MySQL aplikasi. Jalankan php vendor/phpunit/phpunit/phpunit dan npm run build. Tes mencakup pembayaran bank/e-wallet, cash, unggah bukti, pengiriman ulang, penolakan/rollback, isolasi kelas, penanggung jawab, penghapusan nilai, satu sidebar, autentikasi dan laporan error.

Cadangan file sumber dan snapshot pembagian kelas ada di storage/app/repair-backups. Jalankan php artisan migrate untuk menerapkan perubahan skema pada salinan proyek lain. Untuk mulai menerima transfer, bendahara perlu mengisi nomor rekening/e-wallet yang sebenarnya lewat aplikasi.

## Kembalian pembayaran cash

Formulir cash memisahkan nominal pembayaran kas dan uang yang diterima bendahara. Kembalian ditampilkan otomatis, dihitung ulang di server, dan disimpan bersama pembayaran. Uang kurang ditolak. Hanya nominal kas yang menambah saldo, bukan seluruh uang yang diserahkan siswa. Kembalian juga tampil pada riwayat bendahara dan siswa. Catatan cash lama tetap disimpan tanpa mengarang nilai uang diterima/kembaliannya.
