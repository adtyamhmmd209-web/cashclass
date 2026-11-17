@extends('layouts.support')
@section('title', 'Laporan error masuk')
@section('content')<div class="cc-page-heading"><h1>Laporan error masuk</h1><a class="cc-button-primary" href="{{ route('error-reports.create') }}">Kirim laporan baru</a></div>
@forelse($reports as $report)<article>
<h2>{{ $report['summary'] }}</h2><p>{{ $report['created_at'] }} &middot; {{ $report['status'] === 'resolved' ? 'Selesai' : 'Terbuka' }}</p>
<p>Referensi: {{ $report['id'] }}</p><p>Halaman: {{ $report['page'] ?: '-' }}<br>Email: {{ $report['email'] ?? '-' }}</p>
<p class="message">{{ $report['message'] }}</p>
<form method="POST" action="{{ route('error-reports.update', $report['id']) }}">@csrf @method('PATCH')
<input type="hidden" name="status" value="{{ $report['status'] === 'resolved' ? 'open' : 'resolved' }}">
<button type="submit">{{ $report['status'] === 'resolved' ? 'Buka kembali' : 'Tandai selesai' }}</button></form></article>
@empty<p>Belum ada laporan error.</p>@endforelse
@endsection
