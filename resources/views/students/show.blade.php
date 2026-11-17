@extends('layouts.bendahara')
@section('title', 'Detail akun')
@section('content')
<div class="cc-page-heading">
    <div>
        <h1>{{ $student->name }}</h1>
        <p class="cc-muted"><span class="cc-badge">{{ $student->role === 'bendahara' ? 'Bendahara' : 'Siswa' }}</span></p>
    </div>
    <a href="{{ route('students.index') }}">Kembali</a>
</div>
<div class="cc-panel" style="max-width:640px">
    <p><strong>Username:</strong> {{ $student->username }}</p>
    <p><strong>Email:</strong> {{ $student->email }}</p>
    <p><strong>Telepon:</strong> {{ $student->phone ?? '-' }}</p>
    <div class="cc-actions">
        <a href="{{ route('students.edit', $student) }}" class="cc-button-primary">Edit akun</a>
    </div>
</div>
@endsection
