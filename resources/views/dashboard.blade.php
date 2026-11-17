@extends('layouts.bendahara')

@section('title', 'Dashboard')

@section('content')
<!-- Greeting -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Halo, {{ auth()->user()->name }} 👋</h1>
    <p class="text-gray-500 mt-1">Kelola keuangan kelas dengan mudah, cepat dan transparan.</p>
</div>

<!-- Stat Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-5 mb-6">
    <!-- Total Pemasukan -->
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-1">Total Pemasukan</p>
        <p class="text-xl font-bold text-gray-800">Rp {{ number_format($totalIncome, 0, ',', '.') }}</p>
        <p class="text-xs text-green-600 mt-2">Total transaksi tersimpan</p>
    </div>

    <!-- Total Pengeluaran -->
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-1">Total Pengeluaran</p>
        <p class="text-xl font-bold text-gray-800">Rp {{ number_format($totalExpense, 0, ',', '.') }}</p>
        <p class="text-xs text-red-600 mt-2">Total transaksi tersimpan</p>
    </div>

    <!-- Saldo Kas -->
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-1">Saldo Kas</p>
        <p class="text-xl font-bold text-gray-800">Rp {{ number_format($balance, 0, ',', '.') }}</p>
        <p class="text-xs text-gray-500 mt-2">Saat ini</p>
    </div>

    <!-- Jumlah Siswa -->
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-1">Jumlah Siswa</p>
        <p class="text-xl font-bold text-gray-800">{{ $studentCount }}</p>
        <p class="text-xs text-gray-500 mt-2">Siswa aktif</p>
    </div>
</div>

<!-- Chart & Recent Transactions -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
    <!-- Chart -->
    <div class="lg:col-span-2 bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-gray-800">Grafik Arus Kas (7 Hari Terakhir)</h2>
            <div class="flex items-center gap-4 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                    <span class="text-gray-600">Pemasukan</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                    <span class="text-gray-600">Pengeluaran</span>
                </div>
            </div>
        </div>
        <div class="h-64">
            <canvas id="cashFlowChart"></canvas>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-800">Transaksi Terbaru</h2>
            <a href="{{ route('transactions.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Lihat Semua</a>
        </div>
        <div class="space-y-4">
            @forelse($recentTransactions as $transaction)
            <a href="{{ route('transactions.show', $transaction->id) }}" class="block border-b border-gray-100 py-3">
            <p class="text-sm font-medium text-gray-800">{{ $transaction->description }}</p><p class="text-xs text-gray-500">{{ $transaction->transaction_date->format('d M Y') }}</p>
            <p class="text-sm font-semibold {{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }}">{{ $transaction->type === 'income' ? '+' : '-' }} {{ $transaction->formatted_amount }}</p></a>
            @empty<p class="text-sm text-gray-500">Belum ada transaksi.</p>@endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
    const ctx = document.getElementById('cashFlowChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Pemasukan',
                    data: @json($chartIncome),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981',
                },
                {
                    label: 'Pengeluaran',
                    data: @json($chartExpense),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#ef4444',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return (value / 1000000).toFixed(1) + 'jt';
                        }
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
});
</script>
@endpush

<div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm mt-6">
    <h2 class="font-bold text-lg mb-4">Rekap Per Kategori</h2>
    <table class="w-full text-left">
        <thead>
            <tr class="border-b">
                <th class="py-2">Kategori</th>
                <th class="py-2">Jumlah Transaksi</th>
                <th class="py-2">Total Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rekapPerKategori ?? [] as $row)
                <tr class="border-b">
                    <td class="py-2">{{ $row->category_id }}</td>
                    <td class="py-2">{{ $row->total_data }} transaksi</td>
                    <td class="py-2">Rp {{ number_format($row->total_nominal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="py-2 text-gray-500">Belum ada transaksi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection