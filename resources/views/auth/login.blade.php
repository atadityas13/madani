@extends('layouts.base')

@section('title', 'Masuk')

@section('body')
<div class="min-vh-100 d-flex align-items-center justify-content-center p-3">
    <div class="madani-card p-4 p-md-5" style="width: 100%; max-width: 420px;">
        <div class="madani-brand text-success mb-2">MADANI</div>
        <h1 class="h4 mb-1">Masuk operator</h1>
        <p class="text-secondary small mb-4">Gunakan username atau email yang terdaftar.</p>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Username / email</label>
                <input class="form-control @error('login') is-invalid @enderror" type="text" name="login" value="{{ old('login') }}" required autofocus>
                @error('login') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
    </div>
</div>
@endsection
