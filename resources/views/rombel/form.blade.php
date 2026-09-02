@extends('layouts.app')

@php
    $baru = ! $rombel->exists;
    $gtksOptions = $gtks->mapWithKeys(fn ($gtk) => [$gtk->id => $gtk->nama])->all();
@endphp

@section('title', $baru ? 'Tambah rombel' : 'Ubah rombel')
@section('heading', $baru ? 'Tambah rombel' : 'Ubah rombel')
@section('subheading', $tahunAktif?->label() ?? 'Rombongan Belajar')

@section('content')
<div class="madani-card p-4" style="max-width: 720px;">
    <form method="POST" action="{{ $baru ? route('rombel.store') : route('rombel.update', $rombel) }}">
        @csrf
        @unless ($baru)
            @method('PUT')
        @endunless
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tingkat kelas</label>
                <x-emis-select name="tingkat" :options="config('emis.tingkat_rombel')" :value="old('tingkat', $rombel->tingkat)" required />
                @error('tingkat') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-8">
                <label class="form-label">Nama rombel</label>
                <input class="form-control @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama', $rombel->nama) }}" placeholder="A" required>
                @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Wali kelas</label>
                <x-emis-select name="gtk_id" :options="$gtksOptions" :value="old('gtk_id', $rombel->gtk_id)" />
            </div>
            <div class="col-md-6">
                <label class="form-label">Nama ruangan</label>
                <input class="form-control" name="ruangan" value="{{ old('ruangan', $rombel->ruangan) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Jenis rombel</label>
                <x-emis-select name="jenis_rombel" :options="config('emis.jenis_rombel')" :value="old('jenis_rombel', $rombel->jenis_rombel)" />
            </div>
            <div class="col-md-4">
                <label class="form-label">Waktu mengajar</label>
                <x-emis-select name="waktu_mengajar" :options="config('emis.waktu_mengajar')" :value="old('waktu_mengajar', $rombel->waktu_mengajar)" />
            </div>
            <div class="col-md-4">
                <label class="form-label">Kurikulum</label>
                <x-emis-select name="kurikulum" :options="config('emis.kurikulum')" :value="old('kurikulum', $rombel->kurikulum)" />
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <a class="btn btn-outline-secondary" href="{{ $baru ? route('rombel.index') : route('rombel.show', $rombel) }}">Batal</a>
            <button class="btn btn-madani" type="submit">Simpan</button>
        </div>
    </form>
</div>
@endsection
