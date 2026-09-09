@extends('layouts.app')

@section('title', 'Periode pendataan')
@section('heading', 'Periode pendataan')
@section('subheading', 'Manajemen')

@section('content')
@php
    $tz = 'Asia/Jakarta';
    $startsValue = old('starts_at', $periode?->starts_at?->timezone($tz)->format('Y-m-d\\TH:i'));
    $endsValue = old('ends_at', $periode?->ends_at?->timezone($tz)->format('Y-m-d\\TH:i'));
@endphp

<div class="madani-card p-4 mb-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="stat-label mb-1">Status saat ini</div>
            @if ($sedangTerbuka)
                <span class="badge text-bg-success">Terbuka</span>
                <span class="text-secondary small ms-2">
                    Siswa dapat mengedit biodata di Ta'lim sampai
                    {{ $periode?->ends_at?->timezone($tz)->translatedFormat('d M Y H:i') }} WIB
                </span>
            @elseif ($periode?->is_active)
                <span class="badge text-bg-warning">Aktif, di luar jadwal</span>
                <span class="text-secondary small ms-2">Centang aktif ada, tetapi sekarang di luar rentang waktu.</span>
            @else
                <span class="badge text-bg-secondary">Ditutup</span>
                <span class="text-secondary small ms-2">Siswa tidak dapat mengubah biodata (kecuali belum mengunci pernyataan).</span>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('manajemen.periode-pendataan.update') }}" class="row g-3">
        @csrf
        @method('PUT')

        <div class="col-12">
            <div class="form-check form-switch">
                <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    id="is_active"
                    name="is_active"
                    value="1"
                    @checked(old('is_active', $periode?->is_active))
                >
                <label class="form-check-label" for="is_active">Aktifkan periode pendataan</label>
            </div>
            <div class="form-text">Jika nonaktif, rentang waktu diabaikan dan periode dianggap ditutup.</div>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="starts_at">Mulai</label>
            <input
                class="form-control @error('starts_at') is-invalid @enderror"
                type="datetime-local"
                id="starts_at"
                name="starts_at"
                value="{{ $startsValue }}"
            >
            @error('starts_at')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="ends_at">Selesai</label>
            <input
                class="form-control @error('ends_at') is-invalid @enderror"
                type="datetime-local"
                id="ends_at"
                name="ends_at"
                value="{{ $endsValue }}"
            >
            @error('ends_at')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="judul">Judul di aplikasi siswa</label>
            <input
                class="form-control @error('judul') is-invalid @enderror"
                type="text"
                id="judul"
                name="judul"
                maxlength="160"
                required
                value="{{ old('judul', $periode?->judul ?? 'Lengkapi biodata siswa') }}"
            >
            @error('judul')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="pesan">Pesan / instruksi</label>
            <textarea
                class="form-control @error('pesan') is-invalid @enderror"
                id="pesan"
                name="pesan"
                rows="4"
                maxlength="2000"
            >{{ old('pesan', $periode?->pesan) }}</textarea>
            @error('pesan')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-madani" data-loading-text="Menyimpan…">Simpan</button>
        </div>
    </form>
</div>

<div class="text-secondary small">
    Periode ini mengatur apakah siswa boleh mengubah biodata di Ta'lim. Setelah siswa mengunci pernyataan,
    data tetap terkunci meskipun periode masih terbuka.
</div>
@endsection
