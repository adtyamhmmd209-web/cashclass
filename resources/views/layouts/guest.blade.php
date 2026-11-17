@php
    $register = request()->routeIs('register');
    $authTitle = match (true) {
        $register => 'Daftar',
        request()->routeIs('password.request') => 'Lupa password',
        request()->routeIs('password.reset') => 'Reset password',
        request()->routeIs('password.confirm') => 'Konfirmasi password',
        request()->routeIs('verification.notice') => 'Verifikasi email',
        default => 'Masuk',
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $authTitle }} — CashClass</title><meta name="description" content="CashClass, kelola kas kelas dengan mudah dan transparan. Masuk atau buat akun untuk memulai.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/cashclass-auth.css') }}?v=20261028">
</head>
<body class="cc-auth {{ $register ? 'cc-register' : '' }}">
<div class="cc-shell">
    <aside class="cc-story" aria-label="Tentang CashClass">
        <a href="{{ url('/') }}" class="cc-brand" aria-label="Beranda CashClass">@include('partials.cashclass-logo')</a>
        <div class="cc-story-content">
            <p class="cc-eyebrow">Lebih rapi. Lebih transparan.</p>
            <h1>Kas kelas,<br>jadi <span>lebih mudah.</span></h1>
            <p class="cc-story-description">Satu tempat untuk mencatat, memantau, dan mengelola keuangan kelas bersama.</p>
            <div class="cc-features">
                <div class="cc-feature"><span class="cc-feature-icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="m12 3 8 4v5c0 5-8 9-8 9s-8-4-8-9V7l8-4Z"/><path d="m8 12 3 3 5-6"/></svg></span><div><strong>Transaksi lebih transparan</strong><p>Pemasukan dan pengeluaran tercatat dengan jelas.</p></div></div>
                <div class="cc-feature"><span class="cc-feature-icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="4" y="3" width="16" height="18" rx="3"/><path d="M8 8h8M8 12h5M8 16h7"/></svg></span><div><strong>Semua dalam satu tempat</strong><p>Kelola pembayaran, laporan, dan kebutuhan kelas.</p></div></div>
                <div class="cc-feature"><span class="cc-feature-icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6M21 21v-3a6 6 0 0 0-4-5"/></svg></span><div><strong>Untuk kelas yang lebih kompak</strong><p>Bendahara, siswa, dan wali kelas saling terhubung.</p></div></div>
            </div>
        </div>
        <p class="cc-story-footer"><strong>Urus kasnya, nikmati kebersamaannya.</strong>&copy; {{ date('Y') }} CashClass. Kas Kelas Digital.</p>
    </aside>
    <main class="cc-main">
        <div class="cc-mobile-brand"><a href="{{ url('/') }}" class="cc-brand" aria-label="Beranda CashClass">@include('partials.cashclass-logo')</a></div>
        <section class="cc-card" aria-label="{{ $authTitle }} ke CashClass">
            <a href="{{ url('/') }}" class="cc-brand" aria-label="Beranda CashClass">@include('partials.cashclass-logo')</a>
            @if(in_array($authTitle, ['Masuk', 'Daftar']))
            <nav class="cc-tabs" aria-label="Akses akun"><a href="{{ route('login') }}" @if(!$register) aria-current="page" @endif>Masuk</a><a href="{{ route('register') }}" @if($register) aria-current="page" @endif>Daftar wali kelas</a></nav>
            @else<h2>{{ $authTitle }}</h2><p class="cc-intro">Kelola akses akun CashClass Anda.</p>@endif
            @if(session('error'))<div role="alert" class="cc-notice cc-notice-error">{{ session('error') }}</div>@endif
            {{ $slot }}
        </section>
        <footer class="cc-help"><span>Butuh bantuan?</span><a href="{{ route('error-reports.create') }}">Kirim laporan error</a><span aria-hidden="true">&middot;</span><span>CashClass &copy; {{ date('Y') }}</span></footer>
    </main>
</div>
<script>
document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
    button.addEventListener('click', function () {
        const input = document.getElementById(button.dataset.togglePassword);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.textContent = visible ? 'Sembunyikan' : 'Lihat';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', (visible ? 'Sembunyikan ' : 'Tampilkan ') + input.getAttribute('data-password-label'));
    });
});
</script>
</body></html>
