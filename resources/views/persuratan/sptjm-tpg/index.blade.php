@extends('layouts.app')

@section('title', 'SPTJM TPG')
@section('heading', 'SPTJM TPG')
@section('subheading', 'Surat Pernyataan Tanggung Jawab Mutlak — Tunjangan Profesi Guru')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <form class="d-flex gap-2 flex-grow-1 flex-wrap" method="GET" style="max-width: 520px;">
        <input class="form-control" type="search" name="q" value="{{ $q }}" placeholder="Cari nama, NIP, NUPTK, NRG" style="min-width: 220px;">
        <button class="btn btn-outline-secondary" type="submit">Cari</button>
    </form>
</div>

<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>NUPTK</th>
                    <th>NRG</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gtks as $gtk)
                    <tr>
                        <td>{{ $gtk->nama_lengkap }}</td>
                        <td>{{ $gtk->nuptk ?: '—' }}</td>
                        <td>{{ $gtk->nrg ?: '—' }}</td>
                        <td>{{ $gtk->status === 'aktif' ? 'Aktif' : 'Nonaktif' }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-madani" href="{{ route('persuratan.sptjm-tpg.pdf', $gtk) }}">
                                Unduh PDF
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">Belum ada data pegawai.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($gtks->hasPages())
    <div class="mt-3">{{ $gtks->links() }}</div>
@endif
@endsection
