@extends('layouts.app')

@section('title', 'Portofolio · '.$siswa->nama)
@section('heading', 'Portofolio siswa')
@section('subheading', $siswa->nama)

@section('content')
<div class="portofolio-preview-shell">
    <iframe
        class="portofolio-preview-frame"
        title="Preview portofolio {{ $siswa->nama }}"
        src="{{ route('siswa.portofolio.stream', $siswa) }}"
    ></iframe>

    <a
        class="btn btn-madani portofolio-preview-download"
        href="{{ route('siswa.portofolio.download', $siswa) }}"
    >
        <i class="bi bi-download me-1"></i> Unduh
    </a>
</div>
@endsection
