@extends('layouts.workspace')
@section('title','Notifikasi')
@section('content')
<div class="cc-page-heading"><div><h1>Notifikasi</h1><p class="cc-muted">Pembayaran dan pengumuman terbaru untuk akun Anda.</p></div>
@if($notifications->whereNull('read_at')->count())<form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button type="submit">Tandai semua dibaca</button></form>@endif</div>
<section class="cc-panel">
@forelse($notifications as $notification)
<article class="cc-account-row" style="{{ $notification->read_at ? '' : 'background:#eff6ff' }}"><div><strong>{{ $notification->data['title'] ?? 'Notifikasi' }}</strong><p class="cc-muted" style="margin:6px 0">{{ $notification->data['message'] ?? '' }}</p><small class="cc-muted">{{ $notification->created_at->diffForHumans() }}</small></div><a href="{{ route('notifications.read',$notification->id) }}">{{ $notification->read_at?'Lihat':'Buka & tandai dibaca' }}</a></article>
@empty
<div class="cc-empty">Belum ada notifikasi.</div>
@endforelse
<div style="margin-top:18px">{{ $notifications->links() }}</div>
</section>
@endsection
