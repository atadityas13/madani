@extends('layouts.app')

@section('title', 'Ringkasan')
@section('heading', 'Ringkasan')
@section('subheading', $tahunAktif?->label() ?? 'Belum ada tahun ajaran aktif')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="madani-card p-3">
            <div class="stat-label">Siswa</div>
            <div class="fs-3 fw-bold">{{ $jumlahSiswa }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="madani-card p-3">
            <div class="stat-label">Aktif</div>
            <div class="fs-3 fw-bold">{{ $siswaAktif }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="madani-card p-3">
            <div class="stat-label">Tanpa rombel</div>
            <div class="fs-3 fw-bold">{{ $tanpaRombel }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="madani-card p-3">
            <div class="stat-label">Rombel</div>
            <div class="fs-3 fw-bold">{{ $jumlahRombel }}</div>
        </div>
    </div>
</div>

@if ($tampilkanJurnalRanking)
    <div class="madani-card p-0 mb-4">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div>
                <div class="stat-label mb-0">Peringkat pengisian jurnal</div>
                <div class="small text-secondary">10 guru dengan jurnal terbanyak</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('gtk.monitoring') }}">Monitoring GTK</a>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 4rem;">Rank</th>
                        <th>Nama guru</th>
                        <th>NIP / username</th>
                        <th class="text-end" style="width: 9rem;">Jumlah jurnal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jurnalRanking as $row)
                        <tr>
                            <td>
                                @if ($row['rank'] <= 3)
                                    <span class="badge text-bg-{{ $row['rank'] === 1 ? 'warning' : ($row['rank'] === 2 ? 'secondary' : 'dark') }}">{{ $row['rank'] }}</span>
                                @else
                                    {{ $row['rank'] }}
                                @endif
                            </td>
                            <td>{{ $row['nama'] }}</td>
                            <td class="text-secondary">{{ $row['nip'] ?: '—' }}</td>
                            <td class="text-end fw-semibold">{{ number_format($row['jumlah']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-secondary text-center py-4">Belum ada jurnal pembelajaran yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="madani-card p-4">
    <div class="stat-label mb-2">Langkah awal</div>
    <p class="text-secondary mb-0">Catat siswa baru, lengkapi tab orang tua setelah data masuk, lalu tempatkan ke rombel tahun ajaran aktif. Integrasi PPDB menyusul setelah master ini terisi.</p>
</div>
@endsection
