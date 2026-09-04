@extends('layouts.app')

@php
    $baru = ! $user->exists;
    $gtkOptions = $gtks->mapWithKeys(fn ($gtk) => [$gtk->id => $gtk->nama])->all();
    $peranSaatIni = old('role', $user->exists ? $user->peranUtama() : 'admin');
@endphp

@section('title', $baru ? 'Tambah pengguna' : 'Ubah pengguna')
@section('heading', $baru ? 'Tambah pengguna' : 'Ubah pengguna')
@section('subheading', 'Manajemen akun')

@section('content')
<div class="madani-card p-4" style="max-width: 640px;">
    <form method="POST" action="{{ $baru ? route('pengguna.store') : route('pengguna.update', $user) }}">
        @csrf
        @unless ($baru)
            @method('PUT')
        @endunless
        <div class="mb-3">
            <label class="form-label">Nama</label>
            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Username</label>
                <input class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username', $user->username) }}" required>
                @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Kata sandi{{ $baru ? '' : ' (kosongkan jika tidak diubah)' }}</label>
            <input class="form-control @error('password') is-invalid @enderror" type="password" name="password" @required($baru) minlength="8">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Peran</label>
            <x-emis-select name="role" :options="\App\Support\Peran::labels()" :value="$peranSaatIni" data-peran-user required />
            @error('role') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3" data-gtk-field @hidden(! in_array($peranSaatIni, \App\Support\Peran::butuhGtk(), true))>
            <label class="form-label">GTK</label>
            <x-emis-select name="gtk_id" :options="$gtkOptions" :value="old('gtk_id', $user->gtk_id)" />
            @error('gtk_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            <div class="form-text">Wajib untuk wali kelas / guru Ta'lim. Satu GTK hanya terhubung ke satu akun.</div>
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="is_aktif" value="1" id="is_aktif" @checked(old('is_aktif', $user->is_aktif ?? true))>
            <label class="form-check-label" for="is_aktif">Akun aktif</label>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('pengguna.index') }}">Batal</a>
            <button class="btn btn-madani" type="submit">Simpan</button>
        </div>
    </form>
</div>
@endsection
