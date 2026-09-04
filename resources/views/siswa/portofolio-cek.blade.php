@extends('layouts.base')

@section('title', 'Verifikasi portofolio')

@section('body')
<div class="container py-5" style="max-width: 560px;">
    <div class="madani-card p-4">
        <div class="stat-label mb-2">Verifikasi portofolio</div>
        <h1 class="h4 mb-3">{{ $siswa->nama }}</h1>
        <dl class="row mb-0">
            <dt class="col-5">NISN</dt>
            <dd class="col-7">{{ $siswa->nisn ?: '—' }}</dd>
            <dt class="col-5">NIS</dt>
            <dd class="col-7">{{ $siswa->nis ?: '—' }}</dd>
            <dt class="col-5">Status</dt>
            <dd class="col-7">Dokumen resmi digenerate oleh sistem MADANI MTsN 11 Majalengka</dd>
        </dl>
        <p class="form-text mt-3 mb-0">Tautan ini membuktikan keaslian ringkas dokumen portofolio. Detail biodata lengkap hanya ada pada berkas PDF.</p>
    </div>
</div>
@endsection
