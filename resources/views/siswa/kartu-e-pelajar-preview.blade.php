@extends('layouts.app')

@section('title', 'Kartu E-Pelajar · '.$siswa->nama)
@section('heading', 'Kartu E-Pelajar')
@section('subheading', $siswa->nama)

@section('content')
<div class="portofolio-preview-shell">
    <iframe
        class="portofolio-preview-frame"
        title="Preview kartu e-pelajar {{ $siswa->nama }}"
        src="{{ route('siswa.kartu.stream', $siswa) }}"
    ></iframe>

    <a
        class="btn btn-madani portofolio-preview-download"
        href="{{ route('siswa.kartu.download', $siswa) }}"
    >
        <i class="bi bi-download me-1"></i> Unduh
    </a>
</div>
@endsection
