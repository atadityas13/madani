@extends('layouts.base')

@section('title', 'Masuk')

@section('body')
    <x-auth-login-layout
        title="Masuk admin"
        subtitle="Hanya Super Admin dan Admin. Guru serta siswa masuk lewat aplikasi Ta'lim."
    >
        <form method="POST" action="{{ route('login') }}" data-auth-form data-loading-text="Memverifikasi…">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="login">Username / email</label>
                <input
                    id="login"
                    class="form-control @error('login') is-invalid @enderror"
                    type="text"
                    name="login"
                    value="{{ old('login') }}"
                    autocomplete="username"
                    required
                    autofocus
                >
                @error('login')
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
            <a href="{{ route('privacy-policy') }}">Kebijakan privasi</a>
        </x-slot:footer>
    </x-auth-login-layout>
@endsection
