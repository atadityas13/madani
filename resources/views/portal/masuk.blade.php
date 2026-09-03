@extends('layouts.base')

@section('title', 'Masuk siswa')

@section('body')
<div class="login-shell d-flex align-items-center justify-content-center p-3">
    <div class="login-card">
        <div class="login-brand mb-4">
            <img src="{{ asset('images/logo-madani.png') }}?v={{ filemtime(public_path('images/logo-madani.png')) }}" alt="MADANI — Management Academic Data Native Integration">
        </div>
        <div class="emis-topbar-sub mb-3">MTsN 11 Majalengka</div>
        <h1 class="h5 fw-bold mb-1">Masuk siswa</h1>
        <p class="text-secondary small mb-4">Gunakan NISN dan kata sandi. Password awal adalah tanggal lahir dengan format ddmmyyyy.</p>
        <form method="POST" action="{{ route('siswa.masuk') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">NISN</label>
                <input class="form-control @error('nisn') is-invalid @enderror" type="text" name="nisn" value="{{ old('nisn') }}" inputmode="numeric" maxlength="10" required autofocus>
                @error('nisn') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Kata sandi</label>
                <input class="form-control" type="password" name="password" required>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">Ingat sesi ini</label>
            </div>
            <button class="btn btn-madani w-100" type="submit">Masuk</button>
        </form>
        <p class="text-center small mt-3 mb-0">
            <a href="{{ route('login') }}">Saya operator / guru</a>
        </p>
    </div>
</div>
@endsection
