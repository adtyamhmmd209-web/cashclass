@extends('layouts.walikelas')
@section('title', 'Pengumuman kelas')
@section('content')
<div class="cc-page-heading">
    <div><h1>Pengumuman kelas</h1><p class="cc-muted">Bagikan informasi kepada siswa kelas Anda.</p></div>
</div>
<form method="POST" action="{{ route('walikelas.announcements.store') }}" class="cc-panel">
    @csrf
    <h2>Buat pengumuman</h2>
    <div class="cc-form-grid" style="margin-top:16px">
        <label class="cc-wide">Judul<input name="title" required maxlength="255" value="{{ old('title') }}"></label>
        <label class="cc-wide">Isi pengumuman<textarea name="content" required maxlength="10000" rows="4">{{ old('content') }}</textarea></label>
        <label><span><input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned'))> Sematkan di dashboard siswa</span></label>
        <div class="cc-wide"><button class="cc-button-primary" type="submit">Terbitkan pengumuman</button></div>
    </div>
</form>
<section class="cc-panel">
    <h2>Pengumuman sebelumnya</h2>
    @forelse($records as $record)
        <article class="cc-account-row">
            <div>
                <strong>{{ $record->title }}</strong>
                <p class="cc-muted">{{ $record->created_at->format('d M Y') }}{{ $record->is_pinned ? ' · Disematkan' : '' }}</p>
                <p style="white-space:pre-wrap">{{ $record->content }}</p>
            </div>
            <form method="POST" action="{{ route('walikelas.announcements.destroy', $record) }}" onsubmit="return confirm('Hapus pengumuman ini?')">
                @csrf @method('DELETE')
                <button class="cc-button-danger" type="submit">Hapus</button>
            </form>
        </article>
    @empty
        <div class="cc-empty">Belum ada pengumuman.</div>
    @endforelse
    <div style="margin-top:18px">{{ $records->links() }}</div>
</section>
@endsection
