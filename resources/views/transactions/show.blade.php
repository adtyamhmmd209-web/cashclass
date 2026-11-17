@extends('layouts.bendahara')
@section('title', 'Detail transaksi')
@section('content')
<div class="max-w-3xl mx-auto bg-white rounded-xl p-6 border border-gray-100">
<a href="{{ route('transactions.index') }}" class="text-blue-600">Kembali ke transaksi</a>
<h1 class="text-2xl font-bold my-4">{{ $transaction->title }}</h1>
<dl class="space-y-3"><dt>Tanggal</dt><dd>{{ $transaction->transaction_date->format('d M Y') }}</dd><dt>Jenis</dt><dd>{{ $transaction->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</dd><dt>Nominal</dt><dd>{{ $transaction->formatted_amount }}</dd><dt>Kategori</dt><dd>{{ $transaction->category?->name ?? '-' }}</dd><dt>Dicatat oleh</dt><dd>{{ $transaction->user?->name ?? '-' }}</dd><dt>Keterangan</dt><dd class="whitespace-pre-wrap">{{ $transaction->description }}</dd></dl>
@if($transaction->proof_image)<a href="{{ route('transactions.proof', $transaction->id) }}" target="_blank" rel="noopener" class="text-blue-600 block my-4">Lihat bukti transaksi</a>@endif
<a href="{{ route('transactions.edit', $transaction->id) }}" class="inline-block mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg">Edit transaksi</a>
<form method="POST" action="{{ route('transactions.destroy', $transaction->id) }}" class="mt-4" onsubmit="return confirm('Hapus transaksi ini?')">@csrf @method('DELETE')<button type="submit" class="text-red-600">Hapus transaksi</button></form>
</div>
@endsection
