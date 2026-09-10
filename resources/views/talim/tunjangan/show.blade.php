@extends('layouts.talim-webview')

@section('title', $labelJenis.' · Tunjangan')
@section('heading', $labelJenis)
@section('subheading', $gtk->nama_lengkap)

@section('content')
@if (session('status'))
    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
@endif

<div class="talim-toolbar mb-3">
    <a class="talim-back" href="{{ route('talim.tunjangan.index') }}">← Kembali</a>
</div>

<div class="talim-panel mb-3">
    <div class="small text-secondary mb-2">Identitas</div>
    <div class="fw-semibold mb-1">{{ $gtk->nama_lengkap }}</div>
    <div class="small">NIP: {{ $gtk->nip ?: '—' }} · Gol: {{ $gtk->golongan ?: '—' }}</div>
    <div class="small">{{ $gtk->status_pegawai ?: '—' }} · NUPTK: {{ $gtk->nuptk ?: '—' }}</div>
    <div class="small">NRG: {{ $gtk->nrg ?: '—' }}</div>
</div>

@if ($jenis === 'sptjm')
    <div class="talim-panel">
        <div class="talim-section__title mb-2">Unduh SPTJM TPG</div>
        <p class="small text-secondary mb-3">Surat dihasilkan otomatis dari data guru.</p>
        <form method="POST" action="{{ route('talim.tunjangan.sptjm') }}">
            @csrf
            <label class="form-label" for="tanggal_surat">Tanggal surat</label>
            <input class="form-control mb-3" type="date" name="tanggal_surat" id="tanggal_surat" value="{{ now()->format('Y-m-d') }}" required>
            <div class="d-grid gap-2">
                <button class="btn btn-outline-secondary" type="submit" name="mode" value="download">Unduh PDF</button>
                <button class="btn btn-madani" type="submit" name="mode" value="print" formtarget="_blank">Cetak / Preview</button>
            </div>
        </form>
    </div>
@else
    <div class="talim-panel mb-3">
        @if ($jenis === 'skakpt')
            <form method="GET" class="mb-3">
                <label class="form-label">Tahun anggaran</label>
                <select class="form-select" name="tahun" onchange="this.form.submit()">
                    @foreach ($tahunAnggaranOptions as $tahun)
                        <option value="{{ $tahun }}" @selected((int) $tahunAnggaran === (int) $tahun)>{{ $tahun }}</option>
                    @endforeach
                    @unless (in_array((int) $tahunAnggaran, $tahunAnggaranOptions, true))
                        <option value="{{ $tahunAnggaran }}" selected>{{ $tahunAnggaran }}</option>
                    @endunless
                </select>
            </form>
        @else
            <form method="GET" class="mb-3">
                <label class="form-label">Tahun ajaran</label>
                <select class="form-select" name="tahun_ajaran_id" onchange="this.form.submit()">
                    @foreach ($tahunAjarans as $ta)
                        <option value="{{ $ta->id }}" @selected((int) $tahunAjaran?->id === (int) $ta->id)>{{ $ta->nama }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        @foreach ($rows as $row)
            <div class="talim-incomplete">
                <div class="talim-incomplete__nama">
                    <div class="fw-semibold">{{ $row['label'] }}</div>
                    @if ($row['dokumen'])
                        <div class="small">
                            <a class="talim-row-link" href="{{ route('talim.tunjangan.download', [$jenis, $row['dokumen']]) }}">
                                {{ $row['dokumen']->nama_asli ?: 'PDF tersimpan' }}
                            </a>
                        </div>
                    @else
                        <div class="small text-secondary">Belum ada</div>
                    @endif
                </div>
                <div class="talim-incomplete__tags mt-2 w-100">
                    @if ($row['dokumen'])
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('talim.tunjangan.download', [$jenis, $row['dokumen']]) }}">Unduh</a>
                        <form method="POST" action="{{ route('talim.tunjangan.destroy', [$jenis, $row['dokumen']]) }}" class="d-inline" data-confirm="Hapus PDF periode ini?" data-confirm-title="Hapus">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                        </form>
                    @endif
                    @if ($row['boleh_upload'])
                        <form method="POST" action="{{ route('talim.tunjangan.upload', $jenis) }}" enctype="multipart/form-data" class="w-100 mt-2">
                            @csrf
                            <input type="hidden" name="periode" value="{{ $row['periode'] }}">
                            @if ($jenis === 'skakpt')
                                <input type="hidden" name="tahun_anggaran" value="{{ $tahunAnggaran }}">
                            @else
                                <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaran?->id }}">
                            @endif
                            <div class="d-flex gap-2 align-items-center">
                                <input class="form-control form-control-sm" type="file" name="file" accept=".pdf,application/pdf" required>
                                <button class="btn btn-sm btn-madani flex-shrink-0" type="submit">{{ $row['dokumen'] ? 'Ganti' : 'Unggah' }}</button>
                            </div>
                        </form>
                    @else
                        <span class="talim-tag">Terkunci</span>
                    @endif
                </div>
            </div>
        @endforeach
        @error('file') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
    </div>
@endif
@endsection
