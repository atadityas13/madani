@extends('layouts.base')

@section('title', 'Masuk siswa')

@section('body')
    <x-auth-login-layout
        title="Masuk siswa"
        subtitle="Gunakan NISN dan kata sandi. Password awal adalah tanggal lahir dengan format ddmmyyyy."
    >
        <form method="POST" action="{{ route('siswa.masuk') }}" data-auth-form data-loading-text="Memverifikasi…">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="nisn">NISN</label>
                <input
                    id="nisn"
                    class="form-control @error('nisn') is-invalid @enderror"
                    type="text"
                    name="nisn"
                    value="{{ old('nisn') }}"
                    inputmode="numeric"
                    maxlength="10"
                    autocomplete="username"
                    required
                    autofocus
                >
                @error('nisn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Kata sandi</label>
                <div class="madani-login__password">
                    <input
                        id="password"
                        class="form-control"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                        data-password-input
                    >
                    <button
                        class="madani-login__password-toggle"
                        type="button"
                        data-password-toggle
                        aria-label="Tampilkan kata sandi"
                    >
                        <i class="bi bi-eye" data-password-icon></i>
                    </button>
                </div>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                <label class="form-check-label" for="remember">Ingat sesi ini</label>
            </div>
            <button class="btn madani-login__submit w-100" type="submit">Masuk</button>
        </form>

        <x-slot:footer>
            <a href="{{ route('login') }}">Saya operator / guru</a>
            ·
            <a href="{{ route('privacy-policy') }}">Kebijakan privasi</a>
        </x-slot:footer>
    </x-auth-login-layout>
@endsection
