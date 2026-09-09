@extends('layouts.app')

@section('title', 'Tambah siswa')
@section('heading', 'Tambah siswa')
@section('subheading', 'MTsN 11 Majalengka')

@section('content')
<div class="madani-card p-4">
    <form method="POST" action="{{ route('siswa.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="emis-student-head mb-3">
            @include('siswa.partials.foto-slot', [
                'fieldOnly' => true,
                'fotoUrl' => null,
                'inisial' => 'SW',
            ])
            <div>
                <div class="emis-student-name">Foto siswa</div>
                <div class="emis-student-meta">Opsional · unggah bersama data identitas</div>
            </div>
        </div>
        @include('siswa.partials.form-data-siswa', ['siswa' => null, 'periodik' => null, 'emis' => $emis])
        <div class="emis-actions">
            <a class="btn btn-outline-secondary" href="{{ route('siswa.index') }}">Kembali</a>
            <button class="btn btn-madani" type="submit">Simpan</button>
        </div>
    </form>
</div>
@endsection
