@extends('layouts.base')

@php
    $operator = auth()->user();
    $inisial = strtoupper(substr($operator?->name ?? $operator?->username ?? 'A', 0, 1));
@endphp

@section('body')
<div class="madani-shell d-flex">
    <aside class="madani-sidebar d-none d-lg-flex flex-column align-items-center py-3 px-2">
        <div class="emis-sidebar-brand mb-4" title="MADANI">MD</div>
        <nav class="d-flex flex-column gap-2 w-100">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-fill"></i> Ringkasan
            </a>
            <a href="{{ route('siswa.index') }}" class="{{ request()->routeIs('siswa.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Data siswa
            </a>
        </nav>
        <div class="mt-auto small text-center text-secondary px-1" style="font-size: 10px; line-height: 1.3;">
            MTsN 11 Majalengka
        </div>
    </aside>

    <div class="offcanvas offcanvas-start" tabindex="-1" id="emisSidebar">
        <div class="offcanvas-header">
            <div class="d-flex align-items-center gap-2">
                <div class="emis-sidebar-brand">MD</div>
                <strong>MADANI</strong>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <a class="emis-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-grid-fill"></i> Ringkasan
            </a>
            <a class="emis-nav-link {{ request()->routeIs('siswa.*') ? 'active' : '' }}" href="{{ route('siswa.index') }}">
                <i class="bi bi-people-fill"></i> Data siswa
            </a>
        </div>
    </div>

    <div class="flex-grow-1 d-flex flex-column min-vh-100">
        <header class="madani-topbar px-3 px-md-4 py-2 d-flex justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-link text-secondary d-lg-none p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#emisSidebar">
                    <i class="bi bi-list fs-3"></i>
                </button>
                <div>
                    <div class="emis-topbar-title">@yield('heading', 'MADANI')</div>
                    <div class="emis-topbar-sub">@yield('subheading', 'MTsN 11 Majalengka')</div>
                </div>
            </div>
            <div class="emis-userchip">
                <div class="text-end d-none d-sm-block">
                    <div class="fw-bold" style="font-size: 13px;">{{ $operator?->name ?? $operator?->username }}</div>
                    <div class="emis-topbar-sub">Operator</div>
                </div>
                <div class="emis-userchip-avatar">{{ $inisial }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-link btn-sm text-secondary text-decoration-none" type="submit">Keluar</button>
                </form>
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
