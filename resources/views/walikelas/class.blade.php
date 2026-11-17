@extends('layouts.walikelas')
@section('title', 'Data kelas dan akun')
@section('content')
<div class="cc-page-heading">
    <div>
        <h1>{{ $class?->name ?? 'Kelas belum tersedia' }}</h1>
        <p class="cc-muted">Tahun ajaran {{ $class?->academic_year ?? '-' }} · {{ $rosterCount }} siswa</p>
    </div>
    @if($class)
        <a href="{{ route('walikelas.accounts.create') }}" class="cc-button-primary">Tambah akun</a>
    @endif
</div>

@if($class)
    <section class="cc-panel">
        <h2>Data akun kelas</h2>
        <div style="overflow-x:auto;margin-top:14px">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left">
                        <th class="p-3">Peran</th>
                        <th class="p-3">Nama</th>
                        <th class="p-3">Username</th>
                        <th class="p-3">Email</th>
                    </tr>
                </thead>
                <tbody>
                    @if($currentTreasurer)
                        <tr class="border-t">
                            <td class="p-3">Bendahara (siswa)</td>
                            <td class="p-3">{{ $currentTreasurer->name }}</td>
                            <td class="p-3">{{ $currentTreasurer->username }}</td>
                            <td class="p-3">{{ $currentTreasurer->email }}</td>
                        </tr>
                    @endif
                    @foreach($students as $student)
                        <tr class="border-t">
                            <td class="p-3">Siswa</td>
                            <td class="p-3">{{ $student->name }}</td>
                            <td class="p-3">{{ $student->username }}</td>
                            <td class="p-3">{{ $student->email }}</td>
                        </tr>
                    @endforeach
                    @if(! $currentTreasurer && $students->isEmpty())
                        <tr class="border-t">
                            <td colspan="4" class="p-3">Belum ada akun siswa atau bendahara di kelas.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>

    <section class="cc-panel">
        <h2>Penugasan bendahara</h2>
        @if($currentTreasurer)
            <p class="cc-muted">Bendahara saat ini: {{ $currentTreasurer->name }} ({{ $currentTreasurer->username }}).</p>
        @else
            <p class="cc-muted">Belum ada bendahara yang ditugaskan.</p>
        @endif
        @if($treasurers->isNotEmpty())
            <form method="POST" action="{{ route('walikelas.treasurer.assign') }}" class="cc-form-grid" style="margin-top:18px">
                @csrf
                @method('PUT')
                <label class="cc-wide">Pilih akun bendahara
                    <select name="user_id" required>
                        <option value="">Pilih bendahara</option>
                        @foreach($treasurers as $treasurer)
                            <option value="{{ $treasurer->id }}" @selected(old('user_id', $class->treasurer_id) == $treasurer->id)>{{ $treasurer->name }} · {{ $treasurer->username }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="cc-wide"><button class="cc-button-primary" type="submit">Simpan penugasan</button></div>
            </form>
        @else
            <p class="cc-info">Belum ada akun bendahara lain yang dapat dipilih. Gunakan tombol tambah akun untuk membuat bendahara.</p>
        @endif
    </section>
@else
    <div class="cc-empty">Data kelas belum tersedia pada akun wali kelas ini.</div>
@endif
@endsection
