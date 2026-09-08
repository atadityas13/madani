@extends('layouts.app')

@section('title', 'Detail siswa')
@section('heading', 'Detail Siswa')
@section('subheading', 'MTsN 11 Majalengka')

@section('content')
@php
    $inisial = collect(preg_split('/\s+/', trim($siswa->nama)))
        ->filter()
        ->take(2)
        ->map(fn ($p) => strtoupper(substr($p, 0, 1)))
        ->implode('') ?: 'SW';

    $nilai = fn (?string $value) => filled($value) ? $value : '—';

    $jk = match ($siswa->jenis_kelamin) {
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
        default => '—',
    };

    $kip = $periodik?->tidak_punya_kip
        ? '—'
        : $nilai($periodik?->no_kip);

    $rows = [
        'NIK' => $nilai($siswa->nik),
        'NISN' => $nilai($siswa->nisn),
        'NIS' => $nilai($siswa->nis),
        'KIP' => $kip,
        'TEMPAT LAHIR' => $nilai($siswa->tempat_lahir),
        'TANGGAL LAHIR' => $siswa->tanggal_lahir
            ? $siswa->tanggal_lahir->translatedFormat('d F Y')
            : '—',
        'JENIS KELAMIN' => $jk,
        'AGAMA' => $nilai($siswa->agama),
        'JUMLAH SAUDARA' => $siswa->jumlah_saudara !== null ? (string) $siswa->jumlah_saudara : '—',
        'ANAK KE' => $siswa->anak_ke !== null ? (string) $siswa->anak_ke : '—',
        'HOBI' => $nilai($siswa->hobi),
        'CITA-CITA' => $nilai($siswa->cita_cita),
        'ROMBEL' => $rombel ? $rombel->label() : '—',
        'STATUS' => $nilai(str_replace('_', ' ', (string) $siswa->status_keaktifan)),
    ];
@endphp

<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('siswa.index') }}">Kembali</a>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('siswa.portofolio', $siswa) }}">Portofolio</a>
        @can('update', $siswa)
            <a class="btn btn-madani btn-sm" href="{{ route('siswa.edit', $siswa) }}">Edit data</a>
        @endcan
    </div>
</div>

<div class="madani-card siswa-detail">
    <div class="siswa-detail__grid">
        <aside class="siswa-detail__photo">
            @if ($fotoUrl)
                <img src="{{ $fotoUrl }}" alt="Foto {{ $siswa->nama }}">
            @else
                <div class="siswa-detail__photo-fallback" aria-hidden="true">{{ $inisial }}</div>
            @endif
        </aside>

        <div class="siswa-detail__body">
            <h2 class="siswa-detail__name">{{ $siswa->nama }}</h2>

            <dl class="siswa-detail__list">
                @foreach ($rows as $label => $value)
                    <div class="siswa-detail__row">
                        <dt>{{ $label }}</dt>
                        <dd><span class="siswa-detail__sep">:</span>{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</div>
@endsection
