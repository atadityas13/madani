@extends('layouts.app')

@section('title', 'Mutasi/DO')
@section('heading', 'Mutasi/DO')
@section('subheading', 'Mutasi masuk, keluar, dan dropout')

@section('content')
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $createRoutes = [
        'masuk' => route('mutasi.masuk.create'),
        'keluar' => route('mutasi.keluar.create'),
        'do' => route('mutasi.do.create'),
    ];
    $createLabels = [
        'masuk' => 'Catat mutasi masuk',
        'keluar' => 'Catat mutasi keluar',
        'do' => 'Catat dropout',
    ];
    $confirmMessages = [
        'masuk' => 'Batalkan mutasi masuk dan hapus permanen data siswa ini? Tindakan tidak dapat dibatalkan.',
        'keluar' => 'Batalkan mutasi keluar dan aktifkan kembali siswa?',
        'do' => 'Batalkan dropout dan aktifkan kembali siswa?',
    ];
    $showSekolah = $tab !== 'do';
@endphp

<div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
    <ul class="nav nav-pills">
        @foreach ($tabOptions as $key => $label)
            <li class="nav-item">
                <a class="nav-link @if ($tab === $key) active @endif" href="{{ route('mutasi.index', ['tab' => $key]) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>
    <a class="btn btn-madani" href="{{ $createRoutes[$tab] }}">{{ $createLabels[$tab] }}</a>
</div>

<div class="madani-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 4rem;">No</th>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>NISN</th>
                    @if ($showSekolah)
                        <th>{{ $tab === 'masuk' ? 'Sekolah asal' : 'Sekolah tujuan' }}</th>
                    @endif
                    <th>Alasan</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($mutasis as $mutasi)
                    @php
                        $siswa = $mutasi->siswa;
                        $bisaBatalkanMasuk = $tab === 'masuk' && blank($siswa?->nis);
                        $colspan = $showSekolah ? 7 : 6;
                    @endphp
                    <tr>
                        <td>{{ $mutasis->firstItem() + $loop->index }}</td>
                        <td>{{ $mutasi->tanggal?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if ($siswa)
                                <a href="{{ route('siswa.show', $siswa) }}">{{ $siswa->nama }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $siswa?->nisn ?: '—' }}</td>
                        @if ($showSekolah)
                            <td>
                                {{ $mutasi->nama_sekolah ?: '—' }}
                                @if ($mutasi->jenis_sekolah === 'madrasah' && $mutasi->nomor_dokumen_emis)
                                    <div class="small text-secondary">EMIS: {{ $mutasi->nomor_dokumen_emis }}</div>
                                @endif
                            </td>
                        @endif
                        <td>{{ $mutasi->alasan }}</td>
                        <td>
                            <div class="emis-aksi justify-content-end">
                                @if ($tab === 'masuk' && ! $bisaBatalkanMasuk)
                                    <span class="emis-aksi-btn text-secondary" title="Sudah punya NIS — gunakan Mutasi keluar / DO" aria-disabled="true">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                        <span class="visually-hidden">Tidak bisa batalkan</span>
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('mutasi.batalkan', $mutasi) }}"
                                          data-confirm="{{ $confirmMessages[$tab] }}"
                                          data-confirm-title="Batalkan"
                                          data-loading-text="Membatalkan…">
                                        @csrf
                                        @method('DELETE')
                                        <button class="emis-aksi-btn" type="submit" title="Batalkan">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            <span class="visually-hidden">Batalkan</span>
                                        </button>
                                    </form>
                                @endif
                                <button class="emis-aksi-btn text-secondary" type="button" disabled title="Cetak surat (menyusul)">
                                    <i class="bi bi-printer"></i>
                                    <span class="visually-hidden">Cetak surat</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showSekolah ? 7 : 6 }}" class="text-secondary p-3">
                            Belum ada catatan {{ strtolower($tabOptions[$tab] ?? $tab) }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($mutasis->hasPages())
        <div class="p-3">{{ $mutasis->links() }}</div>
    @endif
</div>
@endsection
