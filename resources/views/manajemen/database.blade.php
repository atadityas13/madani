@extends('layouts.app')

@section('title', 'Database')
@section('heading', 'Database')
@section('subheading', 'Manajemen')

@section('content')
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Impor gagal</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<p class="text-secondary mb-3">
    Kosongkan data per modul untuk mengembalikan aplikasi mendekati kondisi awal.
    Akun web MADANI (Super Admin) tidak dihapus. Impor Excel siswa sudah tersedia; modul lain menyusul.
</p>

<div class="row g-3">
    @foreach ($kartu as $item)
        <div class="col-md-6 col-xl-4">
            <div class="madani-card h-100 p-3 d-flex flex-column">
                <div class="stat-label mb-1">{{ $item['label'] }}</div>
                <div class="small text-secondary mb-3">{{ $item['ringkasan'] }}</div>
                <div class="mt-auto d-flex flex-column gap-2">
                    @if ($item['excel'] && ($item['excel_ready'] ?? false))
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('manajemen.database.siswa.template') }}">Unduh template</a>
                            <button class="btn btn-sm btn-outline-secondary" type="button" disabled title="Segera">Ekspor Excel</button>
                        </div>
                        <form
                            method="POST"
                            action="{{ route('manajemen.database.siswa.impor') }}"
                            enctype="multipart/form-data"
                            class="d-flex flex-wrap gap-2 align-items-center"
                            data-loading-text="Mengimpor…"
                        >
                            @csrf
                            <input class="form-control form-control-sm" type="file" name="file" accept=".xlsx,.xls" required>
                            <button class="btn btn-sm btn-outline-primary" type="submit">Impor Excel</button>
                        </form>
                    @elseif ($item['excel'])
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-sm btn-outline-secondary" type="button" disabled title="Segera">Impor Excel</button>
                            <button class="btn btn-sm btn-outline-secondary" type="button" disabled title="Segera">Ekspor Excel</button>
                        </div>
                    @endif
                    <form
                        method="POST"
                        action="{{ route('manajemen.database.kosongkan', $item['id']) }}"
                        data-confirm="{{ $item['confirm'] }}"
                        data-confirm-title="Kosongkan {{ $item['label'] }}"
                        data-confirm-ok="Kosongkan"
                        data-loading-text="Mengosongkan…"
                    >
                        @csrf
                        <button class="btn btn-sm btn-outline-danger" type="submit">Kosongkan</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
