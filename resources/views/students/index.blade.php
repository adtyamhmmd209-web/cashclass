@extends('layouts.bendahara')
@section('title', 'Data siswa')
@section('content')
<div class="cc-page-heading">
    <div>
        <h1>Data siswa &amp; bendahara</h1>
        <p class="cc-muted">{{ $class?->name ?? 'Kelas belum ditetapkan' }} &middot; {{ $students->total() }} akun</p>
    </div>
    @if(auth()->user()->isWaliKelas())
        <a href="{{ route('walikelas.accounts.create') }}" class="cc-button-primary">Tambah akun</a>
    @endif
</div>

<form method="GET" class="cc-panel" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
    <input name="search" aria-label="Cari siswa atau bendahara" placeholder="Cari nama, NISN, username atau email" value="{{ request('search') }}" class="rounded-lg border-gray-300" style="flex:1;min-width:220px">
    <button type="submit" class="cc-button-primary">Cari</button>
</form>

<div class="cc-panel" style="overflow-x:auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left">
                <th class="p-3">Nama</th>
                <th class="p-3">Peran</th>
                <th class="p-3">NISN</th>
                <th class="p-3">Username</th>
                <th class="p-3">Email</th>
                <th class="p-3">Kelas</th>
                <th class="p-3">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                <tr class="border-t">
                    <td class="p-3">{{ $student->name }}</td>
                    <td class="p-3"><span class="cc-badge">{{ $student->role === 'bendahara' ? 'Bendahara' : 'Siswa' }}</span></td>
                    <td class="p-3">{{ $student->nisn ?? '-' }}</td>
                    <td class="p-3">{{ $student->username }}</td>
                    <td class="p-3">{{ $student->email }}</td>
                    <td class="p-3">{{ $class?->name ?? '-' }}</td>
                    <td class="p-3">
                        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                            <a href="{{ route('students.show', $student) }}">Detail</a>
                            <a href="{{ route('students.edit', $student) }}">Edit</a>
                            @if($student->id !== auth()->id())
                                <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Hapus akun ini? Tindakan ini tidak dapat dibatalkan.')" style="margin:0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="cc-button-danger">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-4">Belum ada akun siswa atau bendahara yang cocok.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $students->links() }}
</div>
@endsection
