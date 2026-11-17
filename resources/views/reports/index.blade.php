@extends('layouts.bendahara')

@section('title', 'Laporan Keuangan')

@section('content')
<!-- Header - Ganti dengan ini -->
<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl lg:text-2xl font-bold text-gray-800">Laporan Keuangan</h1>
        <p class="text-gray-500 mt-1 text-sm">Ringkasan pemasukan, pengeluaran dan saldo kas kelas.</p>
    </div>
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
        <form method="GET" action="{{ route(auth()->user()->isWaliKelas() ? 'walikelas.reports' : 'reports.index') }}" class="flex items-center gap-2">
            <input type="date" name="start_date" value="{{ $startDate }}" 
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <span class="text-gray-500 text-sm">-</span>
            <input type="date" name="end_date" value="{{ $endDate }}" 
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium transition">
                Terapkan
            </button>
        </form>
        <a href="{{ route(auth()->user()->isWaliKelas() ? 'walikelas.reports.print' : 'reports.export.pdf', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Cetak / Simpan PDF
        </a>
    </div>
</div>

<!-- Chart & Detail Table -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 lg:gap-5 mb-6">
    <!-- Chart -->
    <div class="lg:col-span-2 bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
        <h2 class="text-lg font-bold text-gray-800 mb-6">Grafik Pemasukan & Pengeluaran</h2>
        <div class="h-72">
            <canvas id="reportChart"></canvas>
        </div>
    </div>

    <!-- Detail Transactions -->
    <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Rincian Transaksi</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="pb-2 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                        <th class="pb-2 text-left text-xs font-semibold text-gray-600 uppercase">Keterangan</th>
                        <th class="pb-2 text-right text-xs font-semibold text-gray-600 uppercase">Nominal</th>
                        <th class="pb-2 text-right text-xs font-semibold text-gray-600 uppercase">Jenis</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($transactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 text-sm text-gray-600 whitespace-nowrap">{{ $transaction['date'] }}</td>
                        <td class="py-3 text-sm text-gray-800 font-medium">{{ $transaction['description'] }}</td>
                        <td class="py-3 text-sm font-semibold text-right whitespace-nowrap">
                            Rp {{ number_format($transaction['amount'], 0, ',', '.') }}
                        </td>
                        <td class="py-3 text-right">
                            @if($transaction['type'] === 'income')
                                <span class="inline-block px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-medium">Pemasukan</span>
                            @else
                                <span class="inline-block px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full font-medium">Pengeluaran</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>



@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
    const ctx = document.getElementById('reportChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Pemasukan',
                    data: @json($chartIncome),
                    backgroundColor: '#10b981',
                    borderRadius: 6,
                    barPercentage: 0.6,
                },
                {
                    label: 'Pengeluaran',
                    data: @json($chartExpense),
                    backgroundColor: '#ef4444',
                    borderRadius: 6,
                    barPercentage: 0.6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 20,
                        font: { size: 12 }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return (value / 1000).toFixed(0) + 'rb';
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
@endsection