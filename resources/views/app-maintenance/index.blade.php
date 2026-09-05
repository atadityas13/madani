@extends('layouts.app')

@section('title', 'Maintenance Ta\'lim')
@section('heading', 'Maintenance Ta\'lim')
@section('subheading', 'Mode perbaikan untuk aplikasi Android')

@section('content')
@php
    $value = fn (string $key) => old($key, $item?->{$key} ?? $defaults[$key] ?? null);
    $isActive = old('is_active', $item?->is_active ?? $defaults['is_active']);
    $showCountdown = old('show_countdown', $item?->show_countdown ?? $defaults['show_countdown']);
    $endsAtValue = old('ends_at');
    if ($endsAtValue === null && $item?->ends_at) {
        $endsAtValue = $item->ends_at->timezone('Asia/Jakarta')->format('Y-m-d\TH:i');
    }
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
        Opsional: tampilkan countdown sampai waktu selesai.
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

            <hr class="my-4">

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" name="show_countdown" value="1" id="show_countdown"
                    @checked($showCountdown)>
                <label class="form-check-label fw-semibold" for="show_countdown">
                    Tampilkan countdown di aplikasi
                </label>
            </div>

            <div class="mb-3">
                <label class="form-label" for="ends_at">Perkiraan selesai</label>
                <input class="form-control" type="datetime-local" name="ends_at" id="ends_at"
                    value="{{ $endsAtValue }}">
                <div class="form-text">Zona waktu Asia/Jakarta. Contoh tampilan di app: 1 hari 3 jam 2 menit 9 detik.</div>
                @error('ends_at')
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
                <li class="mb-2">Countdown hanya tampilan; maintenance tetap aktif sampai Anda nonaktifkan manual.</li>
                <li>Push FCM dan pengingat lokal perangkat tidak dihentikan.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
