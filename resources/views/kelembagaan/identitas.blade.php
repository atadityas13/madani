@extends('layouts.app')

@section('title', 'Identitas madrasah')
@section('heading', 'Identitas madrasah')
@section('subheading', 'Kelembagaan')

@section('content')
<div class="madani-card p-4" style="max-width: 720px;">
    <div class="stat-label mb-3">Profil lembaga</div>
    <div class="row g-3">
        @foreach ([
            'Nama' => $madrasah['nama'],
            'NPSN' => $madrasah['npsn'] ?: '—',
            'NSM' => $madrasah['nsm'] ?: '—',
            'Jenjang' => $madrasah['jenjang'],
            'Status' => $madrasah['status'],
            'Akreditasi' => $madrasah['akreditasi'] ?: '—',
            'Alamat' => $madrasah['alamat'],
            'Desa' => $madrasah['desa'],
            'Kecamatan' => $madrasah['kecamatan'],
            'Kabupaten/Kota' => $madrasah['kota'],
            'Provinsi' => $madrasah['provinsi'],
            'Kode pos' => $madrasah['kode_pos'],
            'Email' => $madrasah['email'] ?: '—',
            'Situs' => $madrasah['website'] ?: '—',
        ] as $label => $nilai)
            <div class="col-md-6">
                <label class="form-label">{{ $label }}</label>
                <input class="form-control bg-light" value="{{ $nilai }}" readonly>
            </div>
        @endforeach
    </div>
</div>
@endsection
