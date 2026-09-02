@extends('layouts.app')

@section('title', 'Ringkasan')
@section('heading', 'Ringkasan')
@section('subheading', $tahunAktif?->label() ?? 'Belum ada tahun ajaran aktif')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="madani-card p-3">
            <div class="stat-label">Siswa</div>
            <div class="fs-3 fw-bold">{{ $jumlahSiswa }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="madani-card p-3">
            <div class="stat-label">Aktif</div>
            <div class="fs-3 fw-bold">{{ $siswaAktif }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="madani-card p-3">
            <div class="stat-label">Tanpa rombel</div>
            <div class="fs-3 fw-bold">{{ $tanpaRombel }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="madani-card p-3">
            <div class="stat-label">Rombel</div>
            <div class="fs-3 fw-bold">{{ $jumlahRombel }}</div>
        </div>
    </div>
</div>
<div class="madani-card p-4">
    <div class="stat-label mb-2">Langkah awal</div>
    <p class="text-secondary mb-0">Catat siswa baru, lengkapi tab orang tua setelah data masuk, lalu tempatkan ke rombel tahun ajaran aktif. Integrasi PPDB menyusul setelah master ini terisi.</p>
</div>
@endsection
