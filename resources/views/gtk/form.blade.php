@extends('layouts.app')

@php
    $baru = ! $gtk->exists;
@endphp

@section('title', $baru ? 'Tambah GTK' : 'Ubah GTK')
@section('heading', $baru ? 'Tambah GTK' : 'Ubah GTK')
@section('subheading', 'Guru dan Tendik')

@section('content')
<div class="madani-card p-4" style="max-width: 640px;">
    <form method="POST" action="{{ $baru ? route('gtk.store') : route('gtk.update', $gtk) }}">
        @csrf
        @unless ($baru)
            @method('PUT')
        @endunless
        <div class="mb-3">
            <label class="form-label">Nama</label>
            <input class="form-control @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama', $gtk->nama) }}" required>
            @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">NIP</label>
                <input class="form-control" name="nip" value="{{ old('nip', $gtk->nip) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">NUPTK</label>
                <input class="form-control @error('nuptk') is-invalid @enderror" name="nuptk" value="{{ old('nuptk', $gtk->nuptk) }}">
                @error('nuptk') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Jenis kelamin</label>
                <x-emis-select name="jenis_kelamin" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']" :value="old('jenis_kelamin', $gtk->jenis_kelamin)" />
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <x-emis-select name="status" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']" :value="old('status', $gtk->status)" required />
            </div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('gtk.index') }}">Batal</a>
            <button class="btn btn-madani" type="submit">Simpan</button>
        </div>
    </form>
</div>
@endsection
