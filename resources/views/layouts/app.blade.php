@extends('layouts.base')

@php
    $operator = auth()->user();
    $inisial = strtoupper(substr($operator?->name ?? $operator?->username ?? 'A', 0, 1));
@endphp

@section('body')
<div class="madani-shell d-flex" data-madani-shell>
    <aside class="madani-sidebar d-none d-lg-flex">
        @include('layouts.partials.sidebar')
    </aside>

    <div class="offcanvas offcanvas-start emis-offcanvas" tabindex="-1" id="emisSidebar">
        <div class="offcanvas-body p-0 d-flex flex-column h-100">
            @include('layouts.partials.sidebar')
        </div>
    </div>

    <div class="flex-grow-1 d-flex flex-column min-vh-100 min-w-0">
        <header class="madani-topbar px-3 px-md-4 py-2 d-flex justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <button class="btn btn-link text-secondary d-lg-none p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#emisSidebar">
                    <i class="bi bi-list fs-3"></i>
                </button>
                <div class="min-w-0">
                    <div class="emis-topbar-title text-truncate">@yield('heading', 'MADANI')</div>
                    <div class="emis-topbar-sub text-truncate">@yield('subheading', 'MTsN 11 Majalengka')</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                @if ($tahunAktif)
                    @if (auth()->user()?->bisaKelola())
                        <a class="emis-semester-chip d-none d-md-flex" href="{{ route('tahun-ajaran.index') }}" title="Kelola tahun ajaran">
                            {{ $tahunAktif->label() }}
                        </a>
                    @else
                        <span class="emis-semester-chip d-none d-md-flex">{{ $tahunAktif->label() }}</span>
                    @endif
                @endif
                <div class="emis-userchip">
                    <div class="text-end d-none d-sm-block">
                        <div class="fw-bold" style="font-size: 13px;">{{ $operator?->name ?? $operator?->username }}</div>
                        <div class="emis-topbar-sub">{{ $operator?->labelPeran() ?? 'Pengguna' }}</div>
                    </div>
                    <div class="emis-userchip-avatar">{{ $inisial }}</div>
                    <form method="POST" action="{{ route('logout') }}" data-no-loading>
                        @csrf
                        <button class="btn btn-link btn-sm text-secondary text-decoration-none" type="submit">Keluar</button>
                    </form>
                </div>
            </div>
        </header>
        <main class="p-3 p-md-4">
            @yield('content')
        </main>
    </div>
</div>
@endsection
