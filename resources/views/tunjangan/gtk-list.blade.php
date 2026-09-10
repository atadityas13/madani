@extends('layouts.app')

@section('title', $labelJenis.' · Tunjangan')
@section('heading', $labelJenis)
@section('subheading', 'Guru tersertifikasi')

@section('content')
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@php $hasil = session('tunjangan_zip_hasil'); @endphp
@if (is_array($hasil))
    <div class="madani-card p-3 mb-3">
        <div class="fw-semibold mb-2">Hasil impor ZIP</div>
        <div class="small mb-2">Berhasil: {{ $hasil['imported'] ?? 0 }}</div>
        @foreach (['missing' => 'Tidak ketemu guru', 'ambiguous' => 'Nama ambigu', 'invalid' => 'Tidak valid'] as $key => $label)
            @if (! empty($hasil[$key]))
                <div class="mb-2">
                    <div class="small fw-semibold text-secondary">{{ $label }}</div>
                    <ul class="small mb-0">
                        @foreach ($hasil[$key] as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </div>
@endif

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('tunjangan.index') }}">Kembali</a>
</div>

@if ($bolehZip)
    <div class="madani-card p-3 mb-3">
        <div class="stat-label mb-2">Unggah ZIP massal (admin)</div>
        <form method="POST" action="{{ route('tunjangan.jenis.zip', $jenis) }}" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            @if ($jenis === 'skbk')
                <div class="col-md-4">
                    <label class="form-label">Tahun ajaran</label>
                    <select class="form-select" name="tahun_ajaran_id" required>
                        <option value="">Pilih</option>
                        @foreach ($tahunAjarans as $ta)
                            <option value="{{ $ta->id }}" @selected((int) old('tahun_ajaran_id', $tahunAktif?->id) === (int) $ta->id)>{{ $ta->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Semester</label>
                    <select class="form-select" name="semester" required>
                        <option value="1" @selected(old('semester') == 1)>I (Ganjil)</option>
                        <option value="2" @selected(old('semester') == 2)>II (Genap)</option>
                    </select>
                </div>
            @endif
            <div class="col-md-4">
                <label class="form-label">File ZIP</label>
                <input class="form-control" type="file" name="zip" accept=".zip,application/zip" required>
                <div class="form-text">
                    @if ($jenis === 'skmt')
                        Pola: Rekap_Penilaian_SKMT_NAMA_TA2026_Sem1.pdf
                    @else
                        Pola: SKBK_NAMA.pdf
                    @endif
                </div>
            </div>
            <div class="col-md-2">
                <button class="btn btn-madani w-100" type="submit">Impor ZIP</button>
            </div>
        </form>
        @error('zip') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        @error('tahun_ajaran_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>
@endif

<div class="madani-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>NUPTK</th>
                    <th>NRG</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gtks as $gtk)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $gtk->nama_lengkap }}</td>
                        <td>{{ $gtk->nip ?: '—' }}</td>
                        <td>{{ $gtk->nuptk ?: '—' }}</td>
                        <td>{{ $gtk->nrg ?: '—' }}</td>
                        <td>{{ $gtk->status_pegawai ?: '—' }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('tunjangan.jenis.show', [$jenis, $gtk]) }}">Buka</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-secondary">Belum ada guru tersertifikasi (NRG terisi).</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
