@extends('layouts.app')

@section('title', 'Database')
@section('heading', 'Database')
@section('subheading', 'Manajemen')

@section('content')
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<p class="text-secondary mb-3">
    Kosongkan data per modul untuk mengembalikan aplikasi mendekati kondisi awal.
    Akun web MADANI (Super Admin) tidak dihapus. Impor/ekspor Excel menyusul.
</p>

<div class="row g-3">
    @foreach ($kartu as $item)
        <div class="col-md-6 col-xl-4">
            <div class="madani-card h-100 p-3 d-flex flex-column">
                <div class="stat-label mb-1">{{ $item['label'] }}</div>
                <div class="small text-secondary mb-3">{{ $item['ringkasan'] }}</div>
                <div class="mt-auto d-flex flex-wrap gap-2">
                    @if ($item['excel'])
                        <button class="btn btn-sm btn-outline-secondary" type="button" disabled title="Segera">Impor Excel</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" disabled title="Segera">Ekspor Excel</button>
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
