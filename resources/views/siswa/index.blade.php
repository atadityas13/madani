@extends('layouts.app')

@section('title', 'Data siswa')
@section('heading', 'Data siswa')
@section('subheading', 'MTsN 11 Majalengka')

@section('content')
@php
    $periode = $periodePendataan ?? null;
    $periodeAktif = (bool) ($periode?->is_active);
@endphp
<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <form class="d-flex gap-2 flex-grow-1" method="GET" style="max-width: 420px;">
        <input class="form-control" type="search" name="q" value="{{ $q }}" placeholder="Cari nama, NISN, NIS">
        <button class="btn btn-outline-secondary" type="submit">Cari</button>
    </form>
    <div class="d-flex gap-2 flex-wrap">
        @can('create', \App\Models\Siswa::class)
            <button
                type="button"
                class="btn btn-outline-success"
                data-bs-toggle="modal"
                data-bs-target="#modalPeriodePendataan"
            >
                Periode pendataan
                @if ($periodeAktif)
                    <span class="badge text-bg-success ms-1">Aktif</span>
                @else
                    <span class="badge text-bg-secondary ms-1">Nonaktif</span>
                @endif
            </button>
            <a class="btn btn-madani" href="{{ route('siswa.create') }}">Tambah siswa</a>
        @endcan
    </div>
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
                        $rombelLabel = $rombel ? $rombel->label() : '—';
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
                                @can('update', $siswa)
                                    <a class="emis-aksi-btn" href="{{ route('siswa.edit', $siswa) }}" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                        <span class="visually-hidden">Edit</span>
                                    </a>
                                @endcan
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

@can('create', \App\Models\Siswa::class)
<div class="modal fade" id="modalPeriodePendataan" tabindex="-1" aria-labelledby="modalPeriodePendataanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('siswa.periode-pendataan.update') }}">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title" id="modalPeriodePendataanLabel">Periode pendataan siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small text-secondary mb-3">
                    Kartu countdown akan tampil di beranda aplikasi siswa selama periode aktif.
                    Tidak dikirim sebagai notifikasi push.
                </p>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="periodeIsActive"
                        @checked(old('is_active', $periode?->is_active))>
                    <label class="form-check-label fw-semibold" for="periodeIsActive">Aktifkan periode</label>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="periodeJudul">Judul</label>
                    <input class="form-control" type="text" name="judul" id="periodeJudul" maxlength="160" required
                        value="{{ old('judul', $periode?->judul ?: 'Lengkapi biodata') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="periodePesan">Pesan</label>
                    <textarea class="form-control" name="pesan" id="periodePesan" rows="3" maxlength="2000">{{ old('pesan', $periode?->pesan ?: 'Segera lengkapi biodata Anda sampai batas waktu yang ditentukan.') }}</textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="periodeStartsAt">Mulai</label>
                        <input class="form-control" type="datetime-local" name="starts_at" id="periodeStartsAt"
                            value="{{ old('starts_at', $periode?->starts_at?->timezone('Asia/Jakarta')->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="periodeEndsAt">Selesai</label>
                        <input class="form-control" type="datetime-local" name="ends_at" id="periodeEndsAt"
                            value="{{ old('ends_at', $periode?->ends_at?->timezone('Asia/Jakarta')->format('Y-m-d\\TH:i')) }}">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-madani">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection
