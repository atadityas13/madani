@extends('layouts.app')

@section('title', 'Data siswa')
@section('heading', 'Data siswa')
@section('subheading', 'MTsN 11 Majalengka')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <form class="d-flex gap-2 flex-grow-1" method="GET" style="max-width: 420px;">
        <input class="form-control" type="search" name="q" value="{{ $q }}" placeholder="Cari nama, NISN, NIS">
        <button class="btn btn-outline-secondary" type="submit">Cari</button>
    </form>
    <a class="btn btn-madani" href="{{ route('siswa.create') }}">Tambah siswa</a>
</div>
<div class="madani-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 4rem;">No</th>
                    <th>Nama</th>
                    <th>NISN</th>
                    <th>NIS</th>
                    <th>JK (L/P)</th>
                    <th>Tingkat/Rombel</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($siswas as $siswa)
                    @php
                        $rombel = $siswa->rombels->first();
                        $rombelLabel = $rombel ? $rombel->tingkat.' / '.$rombel->nama : '—';
                    @endphp
                    <tr>
                        <td>{{ $siswas->firstItem() + $loop->index }}</td>
                        <td>{{ $siswa->nama }}</td>
                        <td>{{ $siswa->nisn ?: '—' }}</td>
                        <td>{{ $siswa->nis ?: '—' }}</td>
                        <td>{{ $siswa->jenis_kelamin ?: '—' }}</td>
                        <td>{{ $rombelLabel }}</td>
                        <td>
                            <div class="emis-aksi">
                                <a class="emis-aksi-btn" href="{{ route('siswa.show', $siswa) }}" title="Detail">
                                    <i class="bi bi-eye"></i>
                                    <span class="visually-hidden">Detail</span>
                                </a>
                                <a class="emis-aksi-btn" href="{{ route('siswa.edit', $siswa) }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                    <span class="visually-hidden">Edit</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">Belum ada data siswa.</td>
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
