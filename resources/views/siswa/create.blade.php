@extends('layouts.app')

@section('title', 'Tambah siswa')
@section('heading', 'Tambah siswa')
@section('subheading', 'MTsN 11 Majalengka')

@section('content')
<div class="madani-card p-4">
    <form method="POST" action="{{ route('siswa.store') }}" enctype="multipart/form-data">
        @csrf
        @include('siswa.partials.form-data-siswa', ['siswa' => null, 'periodik' => null, 'emis' => $emis])
        <div class="emis-actions">
            <a class="btn btn-outline-secondary" href="{{ route('siswa.index') }}">Kembali</a>
            <button class="btn btn-madani" type="submit">Simpan</button>
        </div>
    </form>
</div>
@endsection
