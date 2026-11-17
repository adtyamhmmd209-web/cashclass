@extends('layouts.support')
@section('title', 'Kirim laporan error')
@section('content')
<h1>Kirim laporan error</h1><p>Jelaskan masalah dan langkah untuk mengulanginya. Laporan tersimpan di CashClass dan dapat diperiksa bendahara. Hindari memasukkan password atau token.</p>
@if(session('reference'))<div role="status" class="notice">Laporan berhasil tersimpan. Nomor referensi: <strong>{{ session('reference') }}</strong></div>@endif
<form method="POST" action="{{ route('error-reports.store') }}">@csrf
<label for="summary">Judul masalah</label><input id="summary" name="summary" maxlength="200" required value="{{ old('summary') }}">
<label for="message">Pesan error dan langkah kejadian</label><textarea id="message" name="message" rows="7" minlength="10" maxlength="5000" required>{{ old('message') }}</textarea>
<label for="page">Halaman yang bermasalah (opsional)</label><input id="page" name="page" maxlength="500" value="{{ old('page', $page) }}">
<label for="email">Email untuk dihubungi (opsional)</label><input id="email" name="email" type="email" maxlength="255" value="{{ old('email') }}">
<button type="submit">Kirim laporan</button></form>
@endsection
