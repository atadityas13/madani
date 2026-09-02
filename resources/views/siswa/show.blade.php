@extends('layouts.app')

@section('title', $siswa->nama)
@section('heading', $siswa->nama)
@section('subheading', 'NISN: '.($siswa->nisn ?: 'belum ada').' · status '.$siswa->status_keaktifan)

@section('content')
<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#data-siswa">Data siswa</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#orang-tua">Orang tua</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#aktivitas">Aktivitas belajar</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#beasiswa">Beasiswa</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#prestasi">Prestasi</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pendidikan-lain">Pendidikan lain</a></li>
</ul>
<div class="tab-content">
    <div class="tab-pane fade show active" id="data-siswa">
        <div class="madani-card p-4">
            <dl class="row mb-0">
                <dt class="col-sm-3">Nama</dt><dd class="col-sm-9">{{ $siswa->nama }}</dd>
                <dt class="col-sm-3">NIK</dt><dd class="col-sm-9">{{ $siswa->nik ?: '—' }}</dd>
                <dt class="col-sm-3">Tempat, tanggal lahir</dt>
                <dd class="col-sm-9">{{ $siswa->tempat_lahir ?: '—' }}, {{ optional($siswa->tanggal_lahir)->translatedFormat('d F Y') ?: '—' }}</dd>
                <dt class="col-sm-3">Jenis kelamin</dt><dd class="col-sm-9">{{ $siswa->jenis_kelamin ?: '—' }}</dd>
                <dt class="col-sm-3">Agama</dt><dd class="col-sm-9">{{ $siswa->agama ?: '—' }}</dd>
            </dl>
        </div>
    </div>
    <div class="tab-pane fade" id="orang-tua">
        <div class="row g-3">
            @foreach ($siswa->orangTuas as $ortu)
                <div class="col-md-4">
                    <div class="madani-card p-3 h-100">
                        <div class="stat-label mb-2">{{ strtoupper($ortu->peran) }}</div>
                        <div>{{ $ortu->nama ?: 'Belum diisi' }}</div>
                        <div class="small text-secondary">NIK {{ $ortu->nik ?: '—' }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="tab-pane fade" id="aktivitas">
        <div class="madani-card p-4">
            @forelse ($siswa->rombels as $rombel)
                <div>{{ $rombel->nama }} · tingkat {{ $rombel->tingkat }} · {{ $rombel->tahunAjaran?->nama }}</div>
            @empty
                <p class="text-secondary mb-0">Belum ditempatkan di rombel (aktif tanpa rombel).</p>
            @endforelse
        </div>
    </div>
    <div class="tab-pane fade" id="beasiswa">
        <div class="madani-card p-4 text-secondary">Belum ada beasiswa.</div>
    </div>
    <div class="tab-pane fade" id="prestasi">
        <div class="madani-card p-4 text-secondary">Belum ada prestasi.</div>
    </div>
    <div class="tab-pane fade" id="pendidikan-lain">
        <div class="madani-card p-4 text-secondary">Tab ini disiapkan mengikuti EMIS; isian menyusul.</div>
    </div>
</div>
@endsection
