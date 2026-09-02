@extends('layouts.base')

@section('body')
<div class="madani-shell d-flex">
    <aside class="madani-sidebar d-none d-lg-flex flex-column p-4">
        <div class="mb-4">
            <div class="madani-brand text-warning">MADANI</div>
            <div class="small opacity-75">MTsN 11 Majalengka</div>
        </div>
        <nav class="d-flex flex-column gap-1">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid"></i> Ringkasan
            </a>
            <a href="{{ route('siswa.index') }}" class="{{ request()->routeIs('siswa.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Data siswa
            </a>
        </nav>
        <div class="mt-auto small opacity-75">Native Integration · EMIS-aligned</div>
    </aside>
    <div class="flex-grow-1 d-flex flex-column min-vh-100">
        <header class="madani-topbar px-4 py-3 d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-semibold">@yield('heading', 'MADANI')</div>
                <div class="small text-secondary">@yield('subheading', 'Management Academic Data Native Integration')</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-sm btn-outline-secondary" type="submit">Keluar</button>
            </form>
        </header>
        <main class="p-4">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@endsection
