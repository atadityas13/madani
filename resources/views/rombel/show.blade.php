@extends('layouts.app')

@section('title', $rombel->label())
@section('heading', 'Rombel '.$rombel->label())
@section('subheading', $rombel->tahunAjaran?->label() ?? 'Rombongan Belajar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <a class="btn btn-outline-secondary" href="{{ route('rombel.index') }}">Kembali</a>
    <div class="d-flex gap-2">
        @can('update', $rombel)
            <button class="btn btn-madani" type="button" data-bs-toggle="modal" data-bs-target="#rombelSiswaModal">Tambah siswa</button>
            <a class="btn btn-outline-secondary" href="{{ route('rombel.edit', $rombel) }}">Ubah rombel</a>
            <form method="POST" action="{{ route('rombel.destroy', $rombel) }}" data-confirm="Hapus rombel ini?" data-confirm-title="Hapus" data-loading-text="Menghapus…">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger" type="submit">Hapus</button>
            </form>
        @endcan
    </div>
</div>

<div class="madani-card p-4 mb-3">
    <div class="stat-label mb-3">Identitas rombel</div>
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Tingkat</label>
            <input class="form-control bg-light" value="{{ $rombel->tingkat }}" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label">Nama</label>
            <input class="form-control bg-light" value="{{ $rombel->nama }}" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label">Wali kelas</label>
            <input class="form-control bg-light" value="{{ $rombel->waliKelas?->nama ?: '—' }}" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label">Ruangan</label>
            <input class="form-control bg-light" value="{{ $rombel->ruangan ?: '—' }}" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label">Jenis</label>
            <input class="form-control bg-light" value="{{ $rombel->jenis_rombel ?: '—' }}" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label">Waktu</label>
            <input class="form-control bg-light" value="{{ $rombel->waktu_mengajar ?: '—' }}" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label">Kurikulum</label>
            <input class="form-control bg-light" value="{{ $rombel->kurikulum ?: '—' }}" readonly>
        </div>
    </div>
</div>

<div class="madani-card p-0">
    <div class="p-4 pb-0">
        <div class="stat-label mb-3">Anggota rombel</div>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NISN</th>
                    <th>JK</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rombel->anggotaAktif as $siswa)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><a href="{{ route('siswa.show', $siswa) }}">{{ $siswa->nama }}</a></td>
                        <td>{{ $siswa->nisn ?: '—' }}</td>
                        <td>{{ $siswa->jenis_kelamin ?: '—' }}</td>
                        <td class="text-end">
                            @can('update', $rombel)
                                <form method="POST" action="{{ route('rombel.anggota.destroy', [$rombel, $siswa]) }}" data-confirm="Keluarkan siswa dari rombel?" data-confirm-title="Keluarkan siswa" data-loading-text="Memproses…">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Keluarkan</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-secondary p-3">Belum ada siswa di rombel ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('update', $rombel)
<div class="modal fade" id="rombelSiswaModal" tabindex="-1" aria-labelledby="rombelSiswaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('rombel.anggota.store', $rombel) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title stat-label mb-0" id="rombelSiswaModalLabel">Tambah siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    @forelse ($kandidat as $siswa)
                        <div class="form-check py-1">
                            <input class="form-check-input" type="checkbox" name="siswa_ids[]" value="{{ $siswa->id }}" id="siswa-{{ $siswa->id }}">
                            <label class="form-check-label" for="siswa-{{ $siswa->id }}">
                                {{ $siswa->nama }}
                                <span class="text-secondary">· {{ $siswa->nisn ?: 'tanpa NISN' }}</span>
                            </label>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">Tidak ada siswa yang bisa ditambahkan. Semua siswa aktif sudah memiliki rombel pada semester ini.</p>
                    @endforelse
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-madani" type="submit" @disabled($kandidat->isEmpty())>Tambah</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection
