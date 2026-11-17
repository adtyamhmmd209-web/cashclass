@extends('layouts.bendahara')
@section('title', 'Tagihan kelas')
@section('content')
<div class="cc-page-heading">
    <div><h1>Tagihan kelas</h1><p class="cc-muted">Buat tagihan per periode dan pantau status pembayaran setiap siswa.</p></div>
    <a class="cc-button-primary" href="{{ route('class-fees.create') }}">Buat tagihan</a>
</div>
@forelse($fees as $fee)
    @php
        $paid = $fee->assignments->filter(fn ($assignment) => $assignment->payments->contains('status', 'approved'))->count();
        $pending = $fee->assignments->filter(fn ($assignment) => $assignment->payments->contains('status', 'pending'))->count();
        $assigned = $fee->assignments->count();
        $hasPayments = $fee->assignments->contains(fn ($assignment) => $assignment->payments->isNotEmpty());
    @endphp
    <section class="cc-panel">
        <div class="cc-page-heading">
            <div>
                <h2>{{ $fee->title }}</h2>
                <p class="cc-muted">{{ $fee->starts_at->format('d M Y') }} – {{ $fee->due_date->format('d M Y') }}</p>
            </div>
            <strong class="cc-payment-amount">Rp {{ number_format($fee->amount,0,',','.') }}</strong>
        </div>
        @if($fee->description)<p>{{ $fee->description }}</p>@endif
        <div class="cc-grid" style="margin-top:16px">
            <div><p class="cc-muted">Penerima</p><strong>{{ $assigned }} siswa</strong></div>
            <div><p class="cc-muted">Lunas</p><strong>{{ $paid }} siswa</strong></div>
            <div><p class="cc-muted">Menunggu verifikasi</p><strong>{{ $pending }} siswa</strong></div>
            <div><p class="cc-muted">Belum lunas</p><strong>{{ max(0, $assigned - $paid) }} siswa</strong></div>
        </div>
        <div class="cc-actions" style="margin-top:16px">
            @if(!$hasPayments)
                <a href="{{ route('class-fees.edit', $fee) }}">Ubah</a>
                <form method="POST" action="{{ route('class-fees.destroy', $fee) }}" onsubmit="return confirm('Hapus tagihan ini?')">
                    @csrf @method('DELETE')
                    <button type="submit">Hapus</button>
                </form>
            @else
                <span class="cc-muted">Perubahan dikunci setelah pembayaran mulai dicatat.</span>
            @endif
        </div>
    </section>
@empty
    <div class="cc-empty">Belum ada tagihan. Buat tagihan pertama untuk siswa kelas Anda.</div>
@endforelse
<div style="margin-top:20px">{{ $fees->links() }}</div>
@endsection
