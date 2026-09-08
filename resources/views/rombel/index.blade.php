@extends('layouts.app')

@section('title', 'Rombongan Belajar')
@section('heading', 'Rombongan Belajar')
@section('subheading', $tahunAktif?->label() ?? 'Belum ada tahun ajaran aktif')

@section('content')
<div class="d-flex align-items-center mb-3 gap-3 flex-wrap">
    <div class="stat-label mb-0">Daftar rombel</div>
    @can('create', \App\Models\Rombel::class)
        @if ($tahunAktif)
            <form method="POST" action="{{ route('rombel.sync-simpatisans') }}" data-loading-text="Menyinkronkan…">
                @csrf
                <button class="btn btn-madani" type="submit">Sync Simpatisans</button>
            </form>
        @endif
    @endcan
</div>

@unless ($tahunAktif)
    <div class="d-none" data-swal-warning="Aktifkan tahun ajaran di menu Kelembagaan sebelum sinkron rombel."></div>
@endunless

<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Tingkat</th>
                    <th>Nama rombel</th>
                    <th>Wali kelas</th>
                    <th>Siswa</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rombels as $rombel)
                    <tr>
                        <td>{{ $rombel->tingkat }}</td>
                        <td>{{ $rombel->nama }}</td>
                        <td>{{ $rombel->waliKelas?->nama ?: '—' }}</td>
                        <td>{{ $rombel->anggota_count }}</td>
                        <td class="text-end">
                            <div class="emis-aksi">
                                <a class="emis-aksi-btn" href="{{ route('rombel.show', $rombel) }}" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $rombel)
                                    <a class="emis-aksi-btn" href="{{ route('rombel.edit', $rombel) }}" title="Ubah">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-secondary p-3">Belum ada rombel pada tahun ajaran ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
