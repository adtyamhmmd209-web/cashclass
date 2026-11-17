@extends('layouts.siswa')
@section('title', 'Tagihan kas')
@section('content')
<div class="cc-page-heading">
    <div><h1>Tagihan kas</h1><p class="cc-muted">Tagihan yang diberikan kepada Anda sejak terdaftar di kelas.</p></div>
</div>
@forelse($assignments as $assignment)
    @php
        $paid = $assignment->payments->contains('status', 'approved');
        $pending = $assignment->payments->contains('status', 'pending');
        $rejected = $assignment->payments->contains('status', 'rejected');
        $status = $paid ? 'Lunas' : ($pending ? 'Menunggu verifikasi' : ($rejected ? 'Pembayaran ditolak' : ($assignment->classFee->due_date->lt(today()) ? 'Terlambat' : 'Belum dibayar')));
    @endphp
    <article class="cc-payment-row">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div>
                <h2>{{ $assignment->classFee->title }}</h2>
                <p class="cc-muted">{{ $assignment->classFee->starts_at->format('d M Y') }} – {{ $assignment->classFee->due_date->format('d M Y') }}</p>
                @if($assignment->classFee->description)<p>{{ $assignment->classFee->description }}</p>@endif
            </div>
            <div>
                <div class="cc-payment-amount">Rp {{ number_format($assignment->classFee->amount,0,',','.') }}</div>
                <span class="cc-badge cc-badge-{{ $paid ? 'approved' : ($pending ? 'pending' : 'rejected') }}">{{ $status }}</span>
            </div>
        </div>
        @if(!$paid && !$pending)
            <div class="cc-actions" style="margin-top:14px"><a class="cc-button-primary" href="{{ route('student.transactions.create', ['class_fee_assignment_id' => $assignment->id]) }}">{{ $rejected ? 'Kirim ulang bukti pembayaran' : 'Kirim pembayaran' }}</a></div>
        @endif
    </article>
@empty
    <div class="cc-empty">Belum ada tagihan untuk akun Anda.</div>
@endforelse
<div style="margin-top:20px">{{ $assignments->links() }}</div>
@endsection
