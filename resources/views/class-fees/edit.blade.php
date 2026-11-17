@extends('layouts.bendahara')
@section('title', 'Ubah tagihan')
@section('content')
<div class="cc-page-heading"><div><h1>Ubah tagihan</h1><p class="cc-muted">Perubahan hanya tersedia sebelum ada pembayaran yang dikirim.</p></div></div>
<form method="POST" action="{{ route('class-fees.update', $fee) }}" class="cc-panel">
    @csrf @method('PUT')
    <div class="cc-form-grid">
        <label class="cc-wide">Nama tagihan<input name="title" required maxlength="150" value="{{ old('title', $fee->title) }}"></label>
        <label>Nominal per siswa (Rp)<input type="number" name="amount" required min="1000" max="999999999" step="0.01" value="{{ old('amount', $fee->amount) }}"></label>
        <label>Mulai ditagihkan<input type="date" name="starts_at" required value="{{ old('starts_at', $fee->starts_at->toDateString()) }}"></label>
        <label>Jatuh tempo<input type="date" name="due_date" required value="{{ old('due_date', $fee->due_date->toDateString()) }}"></label>
        <label class="cc-wide">Keterangan (opsional)<textarea name="description" rows="3" maxlength="5000">{{ old('description', $fee->description) }}</textarea></label>
    </div>
    <div class="cc-actions" style="margin-top:18px"><button class="cc-button-primary" type="submit">Simpan perubahan</button><a href="{{ route('class-fees.index') }}">Batal</a></div>
</form>
@endsection
