@extends('layouts.app')

@php
    $baru = ! $tahunAjaran->exists;
@endphp

@section('title', $baru ? 'Tambah tahun ajaran' : 'Ubah tahun ajaran')
@section('heading', $baru ? 'Tambah tahun ajaran' : 'Ubah tahun ajaran')
@section('subheading', 'Kelembagaan')

@section('content')
<div class="madani-card p-4" style="max-width: 640px;">
    <form method="POST" action="{{ $baru ? route('tahun-ajaran.store') : route('tahun-ajaran.update', $tahunAjaran) }}">
        @csrf
        @unless ($baru)
            @method('PUT')
        @endunless
        <div class="mb-3">
            <label class="form-label">Tahun ajaran</label>
            <input class="form-control @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama', $tahunAjaran->nama) }}" placeholder="2026/2027" required>
            @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Semester</label>
            <x-emis-select name="semester" :options="config('emis.semester')" :value="old('semester', $tahunAjaran->semester)" required />
            @error('semester') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Tanggal mulai</label>
                <input class="form-control @error('tanggal_mulai') is-invalid @enderror" type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', $tahunAjaran->tanggal_mulai?->format('Y-m-d')) }}" required>
                @error('tanggal_mulai') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Tanggal selesai</label>
                <input class="form-control @error('tanggal_selesai') is-invalid @enderror" type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai', $tahunAjaran->tanggal_selesai?->format('Y-m-d')) }}" required>
                @error('tanggal_selesai') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="is_aktif" value="1" id="is_aktif" @checked(old('is_aktif', $tahunAjaran->is_aktif))>
            <label class="form-check-label" for="is_aktif">Jadikan semester aktif</label>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('tahun-ajaran.index') }}">Batal</a>
            <button class="btn btn-madani" type="submit">Simpan</button>
        </div>
    </form>
</div>
@endsection
