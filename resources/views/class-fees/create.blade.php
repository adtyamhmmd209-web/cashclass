@extends('layouts.bendahara')
@section('title', 'Buat tagihan')
@section('content')
<div class="cc-page-heading"><div><h1>Buat tagihan kelas</h1><p class="cc-muted">Tagihan ini diberikan kepada {{ $studentCount }} siswa yang terdaftar sekarang.</p></div></div>
<div class="cc-info">Setiap tagihan dibuat satu per satu untuk periode yang Anda tentukan. Daftar penerima disimpan saat tagihan dibuat; anggota baru tidak otomatis mendapat tagihan lama.</div>
@if($studentCount === 0)<div class="cc-empty">Belum ada siswa di kelas. Tambahkan siswa terlebih dahulu.</div>@else
<form method="POST" action="{{ route('class-fees.store') }}" class="cc-panel">
    @csrf
    <div class="cc-form-grid">
        <label class="cc-wide">Nama tagihan<input name="title" required maxlength="150" value="{{ old('title') }}" placeholder="Contoh: Kas bulan November"></label>
        <label>Nominal per siswa (Rp)<input type="number" name="amount" required min="1000" max="999999999" step="0.01" value="{{ old('amount') }}"></label>
        <label>Mulai ditagihkan<input type="date" name="starts_at" required value="{{ old('starts_at', today()->toDateString()) }}"></label>
        <label>Jatuh tempo<input type="date" name="due_date" required value="{{ old('due_date') }}"></label>
        <label class="cc-wide">Keterangan (opsional)<textarea name="description" rows="3" maxlength="5000">{{ old('description') }}</textarea></label>
    </div>
    <div class="cc-actions" style="margin-top:18px"><button class="cc-button-primary" type="submit">Terbitkan tagihan</button><a href="{{ route('class-fees.index') }}">Batal</a></div>
</form>
@endif
@endsection
