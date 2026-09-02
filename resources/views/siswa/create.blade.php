@extends('layouts.app')

@section('title', 'Tambah siswa')
@section('heading', 'Tambah siswa')
@section('subheading', 'Identitas pokok — NISN dikunci setelah tersimpan')

@section('content')
<div class="madani-card p-4" style="max-width: 720px;">
    <form method="POST" action="{{ route('siswa.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Nama lengkap</label>
                <input class="form-control @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama') }}" required>
                @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Jenis kelamin</label>
                <select class="form-select" name="jenis_kelamin">
                    <option value="">Pilih</option>
                    <option value="L" @selected(old('jenis_kelamin') === 'L')>Laki-laki</option>
                    <option value="P" @selected(old('jenis_kelamin') === 'P')>Perempuan</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">NISN</label>
                <input class="form-control @error('nisn') is-invalid @enderror" name="nisn" value="{{ old('nisn') }}" maxlength="10">
                @error('nisn') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">NIK</label>
                <input class="form-control @error('nik') is-invalid @enderror" name="nik" value="{{ old('nik') }}" maxlength="16">
                @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Tempat lahir</label>
                <input class="form-control" name="tempat_lahir" value="{{ old('tempat_lahir') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Tanggal lahir</label>
                <input class="form-control" type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Agama</label>
                <input class="form-control" name="agama" value="{{ old('agama', 'Islam') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">No. HP</label>
                <input class="form-control" name="no_hp" value="{{ old('no_hp') }}">
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-madani" type="submit">Simpan</button>
            <a class="btn btn-outline-secondary" href="{{ route('siswa.index') }}">Batal</a>
        </div>
    </form>
</div>
@endsection
