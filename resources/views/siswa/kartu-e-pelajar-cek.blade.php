@extends('layouts.base')

@section('title', 'Verifikasi Kartu E-Pelajar')

@section('body')
<div class="container py-5" style="max-width: 640px;">
    <div class="madani-card overflow-hidden" style="border:0;box-shadow:0 18px 40px rgba(4,120,87,.18);">
        <div style="background:linear-gradient(135deg,#064E3B,#047857);color:#fff;padding:1.1rem 1.25rem;">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                      style="width:1.4rem;height:1.4rem;background:#10B981;color:#fff;flex-shrink:0;">
                    <i class="bi bi-shield-check" style="font-size:0.85rem;line-height:1;"></i>
                </span>
                <span style="font-weight:700;letter-spacing:.02em;">Kartu E-Pelajar Terverifikasi</span>
            </div>
            <div style="opacity:.85;font-size:.85rem;">{{ $kartu['madrasah']['nama'] }}</div>
        </div>
        <div class="p-4">
            <div class="d-flex gap-3 align-items-start mb-3">
                @if (! empty($kartu['foto_url']))
                    <img src="{{ $kartu['foto_url'] }}" alt="Foto siswa"
                         style="width:88px;height:110px;object-fit:cover;border-radius:10px;border:2px solid #D1FAE5;">
                @endif
                <div>
                    <h1 class="h4 mb-1" style="color:#064E3B;font-weight:800;">{{ $kartu['nama'] }}</h1>
                    <div class="text-muted" style="font-size:.9rem;">Identitas resmi dari sistem MADANI</div>
                </div>
            </div>
            <dl class="row mb-0" style="row-gap:.35rem;">
                <dt class="col-4 text-muted">NISN</dt>
                <dd class="col-8 mb-0 fw-semibold">{{ $kartu['nisn'] ?: '—' }}</dd>
                <dt class="col-4 text-muted">NIS</dt>
                <dd class="col-8 mb-0 fw-semibold">{{ $kartu['nis'] ?: '—' }}</dd>
                <dt class="col-4 text-muted">TTL</dt>
                <dd class="col-8 mb-0 fw-semibold">{{ $kartu['ttl'] }}</dd>
                <dt class="col-4 text-muted">JK</dt>
                <dd class="col-8 mb-0 fw-semibold">{{ $kartu['jenis_kelamin_label'] }}</dd>
                <dt class="col-4 text-muted">Alamat</dt>
                <dd class="col-8 mb-0 fw-semibold">{{ $kartu['alamat'] }}</dd>
            </dl>
            <p class="form-text mt-3 mb-0">
                QR sah. Data ini diverifikasi melalui MADANI {{ $kartu['madrasah']['nama_singkat'] }}.
            </p>
        </div>
    </div>
</div>
@endsection
