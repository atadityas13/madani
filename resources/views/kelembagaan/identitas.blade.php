@extends('layouts.app')

@section('title', 'Identitas madrasah')
@section('heading', 'Identitas madrasah')
@section('subheading', 'Kelembagaan')

@section('content')
<div class="madani-card p-4" style="max-width: 720px;">
    <div class="stat-label mb-3">Profil lembaga</div>

    @if ($bisaUbah)
        <form method="POST" action="{{ route('kelembagaan.identitas.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="form-label">Logo madrasah</label>
                @if ($madrasah->urlLogo())
                    <div class="mb-2">
                        <img src="{{ $madrasah->urlLogo() }}" alt="Logo madrasah" style="max-height: 88px; width: auto;">
                    </div>
                @endif
                <input class="form-control @error('logo') is-invalid @enderror" type="file" name="logo" accept="image/png,image/jpeg,image/webp">
                @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">PNG, JPG, atau WEBP. Maksimal 2 MB.</div>
            </div>
            <div class="row g-3 mb-4">
                @foreach ([
                    ['nama', 'Nama', true],
                    ['npsn', 'NPSN', false],
                    ['nsm', 'NSM', false],
                    ['jenjang', 'Jenjang', false],
                    ['status', 'Status', false],
                    ['akreditasi', 'Akreditasi', false],
                    ['alamat', 'Alamat', false],
                    ['desa', 'Desa', false],
                    ['kecamatan', 'Kecamatan', false],
                    ['kota', 'Kabupaten/Kota', false],
                    ['provinsi', 'Provinsi', false],
                    ['kode_pos', 'Kode pos', false],
                    ['telepon', 'Telepon', false],
                    ['email', 'Email', false],
                    ['website', 'Situs', false],
                ] as [$name, $label, $wajib])
                    <div class="col-md-6">
                        <label class="form-label">{{ $label }}</label>
                        <input class="form-control @error($name) is-invalid @enderror" name="{{ $name }}" value="{{ old($name, $madrasah->{$name}) }}" @required($wajib) @if ($name === 'email') type="email" @endif>
                        @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @endforeach
            </div>
            <button class="btn btn-madani" type="submit">Simpan</button>
        </form>
    @else
        @if ($madrasah->urlLogo())
            <div class="mb-4">
                <img src="{{ $madrasah->urlLogo() }}" alt="Logo madrasah" style="max-height: 88px; width: auto;">
            </div>
        @endif
        <div class="row g-3">
            @foreach ([
                'Nama' => $madrasah->nama,
                'NPSN' => $madrasah->npsn ?: '—',
                'NSM' => $madrasah->nsm ?: '—',
                'Jenjang' => $madrasah->jenjang ?: '—',
                'Status' => $madrasah->status ?: '—',
                'Akreditasi' => $madrasah->akreditasi ?: '—',
                'Alamat' => $madrasah->alamat ?: '—',
                'Desa' => $madrasah->desa ?: '—',
                'Kecamatan' => $madrasah->kecamatan ?: '—',
                'Kabupaten/Kota' => $madrasah->kota ?: '—',
                'Provinsi' => $madrasah->provinsi ?: '—',
                'Kode pos' => $madrasah->kode_pos ?: '—',
                'Telepon' => $madrasah->telepon ?: '—',
                'Email' => $madrasah->email ?: '—',
                'Situs' => $madrasah->website ?: '—',
            ] as $label => $nilai)
                <div class="col-md-6">
                    <label class="form-label">{{ $label }}</label>
                    <input class="form-control bg-light" value="{{ $nilai }}" readonly>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
