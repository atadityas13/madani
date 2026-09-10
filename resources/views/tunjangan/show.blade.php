@extends('layouts.app')

@section('title', $labelJenis.' · '.$gtk->nama)
@section('heading', $labelJenis)
@section('subheading', $gtk->nama_lengkap)

@section('content')
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="{{ $isAdmin ? route('tunjangan.jenis.index', $jenis) : route('tunjangan.index') }}">Kembali</a>
</div>

<div class="madani-card p-3 mb-3">
    <div class="stat-label mb-2">Identitas guru</div>
    <div class="row g-2 small">
        <div class="col-md-6"><strong>Nama</strong><div>{{ $gtk->nama_lengkap }}</div></div>
        <div class="col-md-3"><strong>NIP</strong><div>{{ $gtk->nip ?: '—' }}</div></div>
        <div class="col-md-3"><strong>Golongan</strong><div>{{ $gtk->golongan ?: '—' }}</div></div>
        <div class="col-md-3"><strong>Status</strong><div>{{ $gtk->status_pegawai ?: '—' }}</div></div>
        <div class="col-md-3"><strong>NUPTK / PegID</strong><div>{{ $gtk->nuptk ?: '—' }}</div></div>
        <div class="col-md-3"><strong>NRG</strong><div>{{ $gtk->nrg ?: '—' }}</div></div>
    </div>
</div>

@if ($jenis === 'sptjm')
    <div class="madani-card p-3">
        <div class="stat-label mb-2">Unduh SPTJM TPG</div>
        <p class="small text-secondary mb-3">Surat dihasilkan otomatis dari data guru (bukan upload berkas).</p>
        <form class="row g-2 align-items-end" method="POST" action="{{ route('tunjangan.sptjm.download', $gtk) }}">
            @csrf
            <div class="col-md-4">
                <label class="form-label" for="tanggal_surat">Tanggal surat</label>
                <input class="form-control" type="date" name="tanggal_surat" id="tanggal_surat" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="col-md-8 d-flex gap-2">
                <button class="btn btn-outline-secondary" type="submit" name="mode" value="download">Unduh PDF</button>
                <button class="btn btn-madani" type="submit" name="mode" value="print" formtarget="_blank">Cetak / Preview</button>
            </div>
        </form>
    </div>
@else
    <div class="madani-card p-3 mb-3">
        @if ($jenis === 'skakpt')
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label">Tahun anggaran</label>
                    <select class="form-select" name="tahun" onchange="this.form.submit()">
                        @foreach ($tahunAnggaranOptions as $tahun)
                            <option value="{{ $tahun }}" @selected((int) $tahunAnggaran === (int) $tahun)>{{ $tahun }}</option>
                        @endforeach
                        @unless (in_array((int) $tahunAnggaran, $tahunAnggaranOptions, true))
                            <option value="{{ $tahunAnggaran }}" selected>{{ $tahunAnggaran }}</option>
                        @endunless
                    </select>
                </div>
            </form>
        @else
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-md-4">
                    <label class="form-label">Tahun ajaran</label>
                    <select class="form-select" name="tahun_ajaran_id" onchange="this.form.submit()">
                        @foreach ($tahunAjarans as $ta)
                            <option value="{{ $ta->id }}" @selected((int) $tahunAjaran?->id === (int) $ta->id)>{{ $ta->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        @endif

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 4rem;">No</th>
                        <th>{{ $jenis === 'skakpt' ? 'Bulan' : 'Semester' }}</th>
                        <th>Berkas</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['label'] }}</td>
                            <td>
                                @if ($row['dokumen'])
                                    <a href="{{ route('tunjangan.jenis.download', [$jenis, $gtk, $row['dokumen']]) }}">
                                        {{ $row['dokumen']->nama_asli ?: 'PDF tersimpan' }}
                                    </a>
                                @else
                                    <span class="text-secondary">Belum ada</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                    @if ($row['dokumen'])
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('tunjangan.jenis.download', [$jenis, $gtk, $row['dokumen']]) }}">Unduh</a>
                                        <form method="POST" action="{{ route('tunjangan.jenis.destroy', [$jenis, $gtk, $row['dokumen']]) }}" data-confirm="Hapus PDF periode ini?" data-confirm-title="Hapus" data-loading-text="Menghapus…">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                        </form>
                                    @endif
                                    @if ($row['boleh_upload'])
                                        <form method="POST" action="{{ route('tunjangan.jenis.upload', [$jenis, $gtk]) }}" enctype="multipart/form-data" class="d-inline-flex gap-1 align-items-center">
                                            @csrf
                                            <input type="hidden" name="periode" value="{{ $row['periode'] }}">
                                            @if ($jenis === 'skakpt')
                                                <input type="hidden" name="tahun_anggaran" value="{{ $tahunAnggaran }}">
                                            @else
                                                <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaran?->id }}">
                                            @endif
                                            <input class="form-control form-control-sm" type="file" name="file" accept=".pdf,application/pdf" required style="max-width: 11rem;">
                                            <button class="btn btn-sm btn-madani" type="submit">{{ $row['dokumen'] ? 'Ganti' : 'Unggah' }}</button>
                                        </form>
                                    @else
                                        <span class="badge text-bg-light text-secondary">Terkunci</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @error('file') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
    </div>
@endif
@endsection
