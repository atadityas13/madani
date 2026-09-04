@extends('layouts.base')

@section('title', 'Verifikasi portofolio')

@section('body')
<div class="container py-5" style="max-width: 560px;">
    <div class="madani-card p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                  style="width:1.35rem;height:1.35rem;background:#1877F2;color:#fff;flex-shrink:0;"
                  title="Terverifikasi">
                <i class="bi bi-check-lg" style="font-size:0.85rem;line-height:1;"></i>
            </span>
            <span style="font-weight:700;font-size:1.05rem;color:#111;">Data siswa Terverifikasi</span>
        </div>
        <h1 class="h4 mb-3">{{ $siswa->nama }}</h1>
        <dl class="row mb-0">
            <dt class="col-5">NISN</dt>
            <dd class="col-7">{{ $siswa->nisn ?: '—' }}</dd>
            <dt class="col-5">NIS</dt>
            <dd class="col-7">{{ $siswa->nis ?: '—' }}</dd>
            <dt class="col-5">Sistem</dt>
            <dd class="col-7">MADANI MTsN 11 Majalengka</dd>
        </dl>
        <p class="form-text mt-3 mb-0">
            Data siswa terverifikasi pada sistem MADANI MTsN 11 Majalengka.
        </p>
    </div>
</div>
@endsection
