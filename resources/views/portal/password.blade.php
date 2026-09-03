@extends('layouts.siswa')

@section('title', 'Ubah kata sandi')
@section('heading', 'Ubah kata sandi')
@section('subheading', 'Wajib diubah sebelum melengkapi data')

@section('content')
<div class="madani-card p-4" style="max-width: 480px;">
    <p class="text-secondary small">Password awal memakai tanggal lahir (ddmmyyyy). Ganti dengan kata sandi baru minimal 8 karakter.</p>
    <form method="POST" action="{{ route('siswa.password.update') }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">Kata sandi saat ini</label>
            <input class="form-control @error('current_password') is-invalid @enderror" type="password" name="current_password" required>
            @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Kata sandi baru</label>
            <input class="form-control @error('password') is-invalid @enderror" type="password" name="password" required>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
            <label class="form-label">Ulangi kata sandi baru</label>
            <input class="form-control" type="password" name="password_confirmation" required>
        </div>
        <button class="btn btn-madani" type="submit">Simpan kata sandi</button>
    </form>
</div>
@endsection
