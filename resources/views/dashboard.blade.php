@extends('layouts.app')

@section('title', 'Ringkasan')
@section('heading', 'Ringkasan')
@section('subheading', $tahunAktif?->nama ?? 'Belum ada tahun ajaran aktif')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="madani-card p-3">
            <div class="stat-label">Siswa</div>
            <div class="fs-3 fw-semibold">{{ $jumlahSiswa }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="madani-card p-3">
            <div class="stat-label">Aktif</div>
            <div class="fs-3 fw-semibold">{{ $siswaAktif }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="madani-card p-3">
            <div class="stat-label">Tanpa rombel</div>
            <div class="fs-3 fw-semibold">{{ $tanpaRombel }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="madani-card p-3">
            <div class="stat-label">Rombel</div>
            <div class="fs-3 fw-semibold">{{ $jumlahRombel }}</div>
        </div>
    </div>
</div>
<div class="madani-card p-4">
    <h2 class="h5">Langkah awal</h2>
    <p class="text-secondary mb-0">Catat siswa baru, lengkapi tab orang tua setelah data masuk, lalu tempatkan ke rombel tahun ajaran aktif. Integrasi PPDB menyusul setelah master ini terisi.</p>
</div>
@endsection
