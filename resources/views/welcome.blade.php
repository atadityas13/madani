@extends('layouts.base')

@section('title', 'MADANI')

@section('body')
<div class="container py-5">
    <div class="hero-panel p-4 p-lg-5 mb-4">
        <div class="madani-brand text-warning mb-3">MADANI</div>
        <h1 class="display-6 fw-semibold mb-3">Management Academic Data Native Integration</h1>
        <p class="mb-4 col-lg-8">Master data siswa MTsN 11 Majalengka, selaras struktur EMIS 4.0, siap menjadi sumber identitas untuk PPDB, REDIK, CBT, PRISMA, dan SIPASTI.</p>
        <a class="btn btn-light" href="{{ route('login') }}">Masuk panel</a>
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="madani-card p-4 h-100">
                <div class="stat-label mb-2">Data siswa</div>
                <p class="mb-0">Enam tab EMIS: identitas, orang tua, aktivitas belajar, beasiswa, prestasi, pendidikan lain.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="madani-card p-4 h-100">
                <div class="stat-label mb-2">Rombel</div>
                <p class="mb-0">Tahun ajaran dan rombongan belajar 7/8/9 sebagai tulang kesiswaan, terpisah dari PPDB.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="madani-card p-4 h-100">
                <div class="stat-label mb-2">API hub</div>
                <p class="mb-0">Sanctum token per aplikasi. Identitas kanonik UUID + NISN, bukan salin tabel antar sistem.</p>
            </div>
        </div>
    </div>
</div>
@endsection
