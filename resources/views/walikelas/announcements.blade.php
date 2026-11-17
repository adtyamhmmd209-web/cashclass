@extends('layouts.walikelas')

@section('title', 'Pengumuman')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl lg:text-2xl font-bold text-gray-800">Pengumuman</h1>
        <p class="text-gray-500 mt-1 text-sm">Kelola pengumuman untuk siswa kelas.</p>
    </div>
    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Buat Pengumuman
    </button>
</div>

<!-- Filter Tabs -->
<div class="flex items-center gap-2 mb-4 overflow-x-auto">
    <button class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white whitespace-nowrap">Semua Pengumuman</button>
    <button class="px-4 py-2 rounded-lg text-sm font-medium bg-white text-gray-600 border border-gray-200 whitespace-nowrap">Penting</button>
    <button class="px-4 py-2 rounded-lg text-sm font-medium bg-white text-gray-600 border border-gray-200 whitespace-nowrap">Kegiatan</button>
    <button class="px-4 py-2 rounded-lg text-sm font-medium bg-white text-gray-600 border border-gray-200 whitespace-nowrap">Info</button>
</div>

<!-- Desktop Table -->
<div class="hidden md:block bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">No</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Judul</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kategori</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @php
            $announcements = [
                ['title' => 'Rapat wali kelas XI RPL 1', 'cat' => 'Penting', 'date' => '10 Sep 2025, 08:00', 'status' => 'Aktif'],
                ['title' => 'Ujian Tengah Semester', 'cat' => 'Info', 'date' => '8 Sep 2025, 10:15', 'status' => 'Aktif'],
                ['title' => 'Kegiatan Class Meeting', 'cat' => 'Kegiatan', 'date' => '5 Sep 2025, 14:20', 'status' => 'Aktif'],
                ['title' => 'Pengambilan Raport', 'cat' => 'Info', 'date' => '2 Sep 2025, 09:00', 'status' => 'Aktif'],
                ['title' => 'Libur Maulid Nabi', 'cat' => 'Info', 'date' => '25 Sep 2025, 07:00', 'status' => 'Aktif'],
            ];
            @endphp
            @foreach($announcements as $i => $a)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 text-sm text-gray-600">{{ $i + 1 }}</td>
                <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $a['title'] }}</td>
                <td class="px-6 py-4">
                    @if($a['cat'] === 'Penting')
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">{{ $a['cat'] }}</span>
                    @elseif($a['cat'] === 'Kegiatan')
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">{{ $a['cat'] }}</span>
                    @else
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ $a['cat'] }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">{{ $a['date'] }}</td>
                <td class="px-6 py-4">
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">{{ $a['status'] }}</span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center justify-center gap-2">
                        <button class="p-1.5 text-gray-400 hover:text-yellow-600 hover:bg-yellow-50 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200">
        <p class="text-sm text-gray-600">Menampilkan 1 - 5 dari 12 pengumuman</p>
        <div class="flex items-center gap-1">
            <button class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium">1</button>
            <button class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm text-gray-600">2</button>
            <button class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm text-gray-600">3</button>
        </div>
    </div>
</div>

<!-- Mobile Card View -->
<div class="md:hidden space-y-3">
    @foreach($announcements as $a)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <div class="flex items-start justify-between mb-2">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate">{{ $a['title'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $a['date'] }}</p>
            </div>
            @if($a['cat'] === 'Penting')
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 flex-shrink-0 ml-2">{{ $a['cat'] }}</span>
            @elseif($a['cat'] === 'Kegiatan')
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 flex-shrink-0 ml-2">{{ $a['cat'] }}</span>
            @else
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 flex-shrink-0 ml-2">{{ $a['cat'] }}</span>
            @endif
        </div>
        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">{{ $a['status'] }}</span>
            <div class="flex items-center gap-1">
                <button class="p-1.5 text-gray-400 hover:text-yellow-600 hover:bg-yellow-50 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection