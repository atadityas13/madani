@extends('layouts.app')

@section('title', 'Data siswa')
@section('heading', 'Data siswa')
@section('subheading', 'MTsN 11 Majalengka')

@section('content')
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<form class="siswa-index-toolbar mb-3" method="GET" action="{{ route('siswa.index') }}">
    <div class="siswa-index-toolbar__filters">
        <select class="form-select" name="tingkat" aria-label="Filter tingkat" onchange="this.form.rombel_id.value=''; this.form.submit()">
            <option value="">Semua tingkat</option>
            @foreach ($tingkatOptions as $option)
                <option value="{{ $option }}" @selected($tingkat === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <select class="form-select" name="rombel_id" aria-label="Filter rombel" onchange="this.form.submit()">
            <option value="">Semua rombel</option>
            @foreach ($rombels as $rombel)
                <option value="{{ $rombel->id }}" @selected((string) $rombelId === (string) $rombel->id)>{{ $rombel->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="siswa-index-toolbar__search">
        <input class="form-control" type="search" name="q" value="{{ $q }}" placeholder="Cari nama, NISN, NIS">
        <input type="hidden" name="per_page" value="{{ $perPage }}">
        <button class="btn btn-outline-secondary" type="submit">Cari</button>
    </div>
</form>

<div class="madani-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 4rem;">No</th>
                    <th>Nama</th>
                    <th>NISN</th>
                    <th>
                        NIS
                        @if (($bisaGenerateNis ?? false) && ($jumlahTanpaNis ?? 0) > 0)
                            <button
                                type="button"
                                class="nis-alert-badge"
                                title="Terdapat {{ number_format($jumlahTanpaNis) }} siswa yang belum memiliki NIS"
                                data-bs-toggle="modal"
                                data-bs-target="#generateNisModal"
                                aria-label="Generate NIS"
                            >!</button>
                        @endif
                    </th>
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
                                <a class="emis-aksi-btn" href="{{ route('siswa.portofolio', $siswa) }}" title="Portofolio">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <span class="visually-hidden">Portofolio</span>
                                </a>
                                @can('update', $siswa)
                                    <a class="emis-aksi-btn" href="{{ route('siswa.edit', $siswa) }}" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                        <span class="visually-hidden">Edit</span>
                                    </a>
                                    @if ($siswa->tanggal_lahir)
                                        <form
                                            method="POST"
                                            action="{{ route('siswa.reset-password', $siswa) }}"
                                            data-confirm="Reset password ke tanggal lahir (ddmmyyyy)? Siswa wajib mengubahnya saat masuk."
                                            data-confirm-title="Reset password"
                                            data-loading-text="Mereset…"
                                        >
                                            @csrf
                                            <button class="emis-aksi-btn" type="submit" title="Reset password">
                                                <i class="bi bi-arrow-clockwise"></i>
                                                <span class="visually-hidden">Reset password</span>
                                            </button>
                                        </form>
                                    @endif
                                    @if ($siswa->pernyataan)
                                        <form
                                            method="POST"
                                            action="{{ route('siswa.pernyataan.batalkan', $siswa) }}"
                                            data-confirm="Batalkan konfirmasi dan hapus pernyataan siswa ini? Akses edit data akan dibuka kembali selama periode pendataan terbuka."
                                            data-confirm-title="Batalkan pernyataan"
                                            data-loading-text="Membatalkan…"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button class="emis-aksi-btn emis-aksi-btn--danger" type="submit" title="Batalkan pernyataan">
                                                <i class="bi bi-x-lg"></i>
                                                <span class="visually-hidden">Batalkan pernyataan</span>
                                            </button>
                                        </form>
                                    @endif
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

    @include('siswa.partials.index-pagination', [
        'siswas' => $siswas,
        'q' => $q,
        'tingkat' => $tingkat,
        'rombelId' => $rombelId,
        'perPage' => $perPage,
    ])
</div>

@if ($bisaGenerateNis ?? false)
    <div class="modal fade" id="generateNisModal" tabindex="-1" aria-labelledby="generateNisModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('siswa.generate-nis') }}" data-loading-text="Menggenerate…">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="generateNisModalLabel">Generate NIS</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-secondary mb-3">
                            Format: NSM + 2 digit tahun masuk (dari TA aktif) + 4 digit urutan.
                            Hanya siswa pada angkatan terpilih yang belum punya NIS.
                        </p>
                        <label class="form-label" for="generateNisAngkatan">Angkatan</label>
                        <select class="form-select" id="generateNisAngkatan" name="angkatan" required>
                            <option value="">Pilih angkatan</option>
                            @foreach (config('emis.tingkat_rombel') as $kode => $label)
                                <option value="{{ $kode }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-madani">Generate NIS</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
