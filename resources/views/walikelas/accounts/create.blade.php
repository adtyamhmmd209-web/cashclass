@extends('layouts.walikelas')
@section('title', 'Tambah akun')
@section('content')
<div class="cc-page-heading">
    <div>
        <h1>Tambah akun</h1>
        <p class="cc-muted">Buat akun siswa atau bendahara untuk {{ $class->name }}.</p>
    </div>
    <a href="{{ route('walikelas.class') }}">Kembali</a>
</div>
@if($class->treasurer)
    <p class="cc-info">Jika memilih Bendahara, akun baru akan langsung ditugaskan ke kelas ini dan menggantikan {{ $class->treasurer->name }}.</p>
@else
    <p class="cc-info">Jika memilih Bendahara, akun baru akan langsung ditugaskan ke kelas ini.</p>
@endif

<form method="POST" action="{{ route('walikelas.accounts.store') }}" class="cc-panel">
    @csrf
    <div class="cc-form-grid">
        <label class="cc-wide">Jenis akun
            <select name="account_type" required>
                <option value="siswa" @selected(old('account_type', 'siswa') === 'siswa')>Siswa</option>
                <option value="bendahara" @selected(old('account_type') === 'bendahara')>Bendahara</option>
            </select>
            @error('account_type')<span class="cc-error">{{ $message }}</span>@enderror
        </label>
        <label>Nama lengkap
            <input name="name" required maxlength="255" value="{{ old('name') }}" autocomplete="name">
            @error('name')<span class="cc-error">{{ $message }}</span>@enderror
        </label>
        <label>NISN
            <input name="nisn" required minlength="10" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" value="{{ old('nisn') }}" placeholder="10 digit angka">
            @error('nisn')<span class="cc-error">{{ $message }}</span>@enderror
        </label>
        <label>Username
            <input name="username" required maxlength="255" value="{{ old('username') }}" autocomplete="off">
            @error('username')<span class="cc-error">{{ $message }}</span>@enderror
        </label>
        <label>Email
            <input type="email" name="email" required maxlength="255" value="{{ old('email') }}" autocomplete="email">
            @error('email')<span class="cc-error">{{ $message }}</span>@enderror
        </label>
        <label>Telepon (opsional)
            <input name="phone" maxlength="30" value="{{ old('phone') }}" autocomplete="tel">
            @error('phone')<span class="cc-error">{{ $message }}</span>@enderror
        </label>
        <label>Password awal
            <input name="password" type="password" required minlength="8" autocomplete="new-password">
            @error('password')<span class="cc-error">{{ $message }}</span>@enderror
        </label>
        <label>Konfirmasi password
            <input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
        </label>
    </div>
    <div class="cc-actions" style="margin-top:18px">
        <button class="cc-button-primary" type="submit">Buat akun</button>
        <a href="{{ route('walikelas.class') }}">Batal</a>
    </div>
</form>
@endsection
