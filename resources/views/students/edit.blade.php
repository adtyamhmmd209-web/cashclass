@extends('layouts.bendahara')
@section('title', 'Edit akun')
@section('content')
<div class="cc-page-heading">
    <div>
        <h1>Edit akun {{ $student->role === 'bendahara' ? 'bendahara' : 'siswa' }}</h1>
        <p class="cc-muted">{{ $student->name }} &middot; {{ $class->name }}</p>
    </div>
    <a href="{{ route('students.index') }}">Kembali</a>
</div>

<form method="POST" action="{{ route('students.update', $student) }}" class="cc-panel">
    @csrf
    @method('PUT')
    <div class="cc-form-grid">
        <label>Nama lengkap
            <input name="name" required maxlength="255" value="{{ old('name', $student->name) }}" autocomplete="name">
            @error('name')<span class="cc-error">{{ $message }}</span>@enderror
        </label>
        <label>NISN
            <input name="nisn" required minlength="10" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" value="{{ old('nisn', $student->nisn) }}" placeholder="10 digit angka">
            @error('nisn')<span class="cc-error">{{ $message }}</span>@enderror
        </label>
        <label>Username
            <input name="username" required maxlength="255" value="{{ old('username', $student->username) }}" autocomplete="off">
            @error('username')<span class="cc-error">{{ $message }}</span>@enderror
        </label>
        <label>Email
            <input type="email" name="email" required maxlength="255" value="{{ old('email', $student->email) }}" autocomplete="email">
            @error('email')<span class="cc-error">{{ $message }}</span>@enderror
        </label>
        <label>Telepon (opsional)
            <input name="phone" maxlength="30" value="{{ old('phone', $student->phone) }}" autocomplete="tel">
            @error('phone')<span class="cc-error">{{ $message }}</span>@enderror
        </label>
        <label>Password baru (opsional)
            <input name="password" type="password" minlength="8" autocomplete="new-password">
            @error('password')<span class="cc-error">{{ $message }}</span>@enderror
        </label>
        <label>Konfirmasi password baru
            <input name="password_confirmation" type="password" minlength="8" autocomplete="new-password">
        </label>
    </div>
    <p class="cc-muted" style="margin-top:14px">Biarkan kolom password kosong jika tidak ingin mengubah password akun ini.</p>
    <div class="cc-actions">
        <button class="cc-button-primary" type="submit">Simpan perubahan</button>
        <a href="{{ route('students.index') }}">Batal</a>
    </div>
</form>
@endsection
