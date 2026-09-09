@extends('layouts.app')

@section('title', 'Dashboard vendor')
@section('heading', 'Dashboard vendor')
@section('subheading', 'Foto dan cetak kartu e-pelajar')

@section('content')
@php
    $linkSudah = $jobPertama
        ? route('vendor.jobs.show', ['vendorJob' => $jobPertama, 'foto' => 'sudah'])
        : route('vendor.jobs.index', ['foto' => 'sudah']);
    $linkBelum = $jobPertama
        ? route('vendor.jobs.show', ['vendorJob' => $jobPertama, 'foto' => 'belum'])
        : route('vendor.jobs.index', ['foto' => 'belum']);
    $linkSemua = $jobPertama
        ? route('vendor.jobs.show', $jobPertama)
        : route('vendor.jobs.index');
@endphp
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <a class="text-decoration-none" href="{{ $linkSemua }}">
            <div class="madani-card p-3 vendor-stat h-100">
                <div class="stat-label">Jumlah siswa</div>
                <div class="fs-2 fw-bold text-dark">{{ number_format($statistik['total']) }}</div>
                <div class="small text-secondary mt-1">Dari job aktif</div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a class="text-decoration-none" href="{{ $linkSudah }}">
            <div class="madani-card p-3 vendor-stat h-100">
                <div class="stat-label">Sudah upload foto</div>
                <div class="fs-2 fw-bold text-success">{{ number_format($statistik['sudah_foto']) }}</div>
                <div class="small text-secondary mt-1">Klik untuk filter sudah foto</div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a class="text-decoration-none" href="{{ $linkBelum }}">
            <div class="madani-card p-3 vendor-stat h-100">
                <div class="stat-label">Belum upload foto</div>
                <div class="fs-2 fw-bold text-danger">{{ number_format($statistik['belum_foto']) }}</div>
                <div class="small text-secondary mt-1">Klik untuk filter belum foto</div>
            </div>
        </a>
    </div>
</div>

<div class="madani-card p-4 mb-4">
    <div class="stat-label mb-2">Panduan alur</div>
    <ol class="mb-3 ps-3 text-secondary">
        <li class="mb-2">Buka <strong>Job saya</strong>, unggah foto satuan (drag-drop + crop 3:4) atau ZIP hotfolder <code>NISN.jpg</code> / <code>NISN.png</code>.</li>
        <li class="mb-2">Filter rombel / status foto untuk memastikan tidak ada yang terlewat.</li>
        <li>Centang siswa, lalu <strong>Cetak massal</strong> (depan + belakang berdampingan).</li>
    </ol>
    <div class="small text-secondary border-top pt-3">
        Spek foto: rasio <strong>3:4</strong>, maksimal <strong>500 KB</strong>, JPG/PNG. Lebar minimal 300 px.
    </div>
</div>

@if ($jobs->isNotEmpty())
    <div class="madani-card p-0">
        <div class="p-3 border-bottom"><div class="stat-label mb-0">Job aktif</div></div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Siswa</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jobs as $job)
                        <tr>
                            <td>{{ $job->nama }}</td>
                            <td>{{ number_format($job->siswas_count) }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-madani" href="{{ route('vendor.jobs.show', $job) }}">Buka</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
