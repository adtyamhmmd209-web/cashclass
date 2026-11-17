@extends('layouts.walikelas')
@section('title', 'Dashboard wali kelas')
@section('content')<h1 class="text-2xl font-bold mb-4">{{ $class?->name ?? 'Kelas belum ditetapkan' }}</h1>
<div class="grid sm:grid-cols-3 gap-4 mb-6">
    @foreach([
        'Siswa'=>$students->count(),
        'Pemasukan'=>'Rp '.number_format($income,0,',','.'),
        'Saldo kas'=>'Rp '.number_format($income-$expense,0,',','.'),
        'Belum lunas'=>'Rp '.number_format($feeOutstanding,0,',','.'),
        'Terkumpul dari tagihan'=>'Rp '.number_format($feeCollected,0,',','.'),
        'Tunggakan jatuh tempo'=>'Rp '.number_format($feeOverdueTotal,0,',','.'),
    ] as $label=>$value)
        <div class="bg-white p-5 rounded-xl"><p>{{ $label }}</p><strong class="text-xl">{{ $value }}</strong></div>
    @endforeach
</div>
<section class="bg-white rounded-xl p-5 mb-6">
    <h2 class="font-bold text-lg mb-1">Tagihan belum lunas</h2>
    <p class="text-sm text-slate-500 mb-4">Siswa dengan saldo tagihan yang masih terbuka.</p>
    @forelse($studentsWithFeesDue as $student)
        <article class="border-b py-3 flex justify-between gap-4">
            <div><h3 class="font-semibold">{{ $student['name'] }}</h3><p class="text-sm text-slate-500">{{ $student['count'] }} tagihan belum lunas</p></div>
            <strong>Rp {{ number_format($student['amount'],0,',','.') }}</strong>
        </article>
    @empty
        <p>Semua tagihan yang sudah dimulai telah lunas.</p>
    @endforelse
</section>
<section class="bg-white rounded-xl p-5"><h2 class="font-bold text-lg mb-4">Pengumuman kelas</h2>@forelse($announcements as $announcement)<article class="border-b py-3"><h3 class="font-semibold">{{ $announcement->title }}</h3><p class="whitespace-pre-wrap">{{ $announcement->content }}</p></article>@empty<p>Belum ada pengumuman.</p>@endforelse</section>@endsection
