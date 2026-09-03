@extends('layouts.base')

@php
    $siswa = auth('siswa')->user();
    $inisial = strtoupper(substr($siswa?->nama ?? 'S', 0, 1));
@endphp

@section('body')
<div class="madani-shell d-flex">
    <div class="flex-grow-1 d-flex flex-column min-vh-100 min-w-0">
        <header class="madani-topbar px-3 px-md-4 py-2 d-flex justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <img src="{{ asset('images/logo-madani.png') }}?v={{ filemtime(public_path('images/logo-madani.png')) }}" alt="MADANI" style="height: 36px;">
                <div class="min-w-0">
                    <div class="emis-topbar-title text-truncate">@yield('heading', 'Data saya')</div>
                    <div class="emis-topbar-sub text-truncate">@yield('subheading', 'MTsN 11 Majalengka')</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                @if ($tahunAktif)
                    <span class="emis-semester-chip d-none d-md-flex">{{ $tahunAktif->label() }}</span>
                @endif
                <div class="emis-userchip">
                    <div class="text-end d-none d-sm-block">
                        <div class="fw-bold" style="font-size: 13px;">{{ $siswa?->nama }}</div>
                        <div class="emis-topbar-sub">Siswa · NISN {{ $siswa?->nisn }}</div>
                    </div>
                    <div class="emis-userchip-avatar">{{ $inisial }}</div>
                    <form method="POST" action="{{ route('siswa.keluar') }}">
                        @csrf
                        <button class="btn btn-link btn-sm text-secondary text-decoration-none" type="submit">Keluar</button>
                    </form>
                </div>
            </div>
        </header>
        <main class="p-3 p-md-4">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@endsection
