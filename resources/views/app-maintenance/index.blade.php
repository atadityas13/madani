@extends('layouts.app')

@section('title', 'Maintenance Ta\'lim')
@section('heading', 'Maintenance Ta\'lim')
@section('subheading', 'Mode perbaikan untuk aplikasi Android')

@section('content')
@php
    $value = fn (string $key) => old($key, $item?->{$key} ?? $defaults[$key] ?? null);
    $isActive = old('is_active', $item?->is_active ?? $defaults['is_active']);
@endphp

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
    <p class="text-secondary mb-0">
        Saat aktif, halaman masuk guru/siswa di Ta'lim menampilkan layar maintenance dengan pesan di bawah.
        Pengingat lokal dan FCM tetap berjalan.
    </p>
    @if ($isActive)
        <span class="badge text-bg-warning align-self-center">Maintenance aktif</span>
    @else
        <span class="badge text-bg-secondary align-self-center">Nonaktif</span>
    @endif
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <form action="{{ route('app-maintenance.store') }}" method="POST" class="madani-card p-4">
            @csrf

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="is_active"
                    @checked($isActive)>
                <label class="form-check-label fw-semibold" for="is_active">
                    Aktifkan mode maintenance
                </label>
            </div>

            <div class="mb-3">
                <label class="form-label">Judul</label>
                <input class="form-control" type="text" name="title" maxlength="160" required
                    value="{{ $value('title') }}">
                @error('title')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Pesan</label>
                <textarea class="form-control" name="message" rows="4" maxlength="2000"
                    placeholder="Penjelasan singkat untuk guru/siswa.">{{ $value('message') }}</textarea>
                @error('message')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-end">
                <button class="btn btn-madani" type="submit">Simpan</button>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="madani-card p-4">
            <div class="text-uppercase small text-secondary fw-semibold mb-2">Catatan</div>
            <ul class="mb-0 small text-secondary ps-3">
                <li class="mb-2">Muncul di halaman login guru dan siswa.</li>
                <li class="mb-2">Tombol konfirmasi mengembalikan user ke pilih peran.</li>
                <li class="mb-2">Login API ditolak selama maintenance aktif.</li>
                <li>Push FCM dan pengingat lokal perangkat tidak dihentikan.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
