@extends('layouts.app')

@section('title', 'Data siswa')
@section('heading', 'Data siswa')
@section('subheading', 'Master identitas selaras EMIS 4.0')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <form class="d-flex gap-2" method="GET">
        <input class="form-control" type="search" name="q" value="{{ $q }}" placeholder="Cari nama, NISN, NIK">
        <button class="btn btn-outline-secondary" type="submit">Cari</button>
    </form>
    <a class="btn btn-madani" href="{{ route('siswa.create') }}">Tambah siswa</a>
</div>
<div class="madani-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>NISN</th>
                    <th>NIK</th>
                    <th>L/P</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($siswas as $siswa)
                    <tr>
                        <td><a href="{{ route('siswa.show', $siswa) }}">{{ $siswa->nama }}</a></td>
                        <td>{{ $siswa->nisn ?: '—' }}</td>
                        <td>{{ $siswa->nik ?: '—' }}</td>
                        <td>{{ $siswa->jenis_kelamin ?: '—' }}</td>
                        <td>{{ str_replace('_', ' ', $siswa->status_keaktifan) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">Belum ada data siswa.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($siswas->hasPages())
        <div class="p-3">{{ $siswas->links() }}</div>
    @endif
</div>
@endsection
