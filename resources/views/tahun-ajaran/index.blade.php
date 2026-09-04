@extends('layouts.app')

@section('title', 'Tahun ajaran')
@section('heading', 'Tahun ajaran')
@section('subheading', 'Kelembagaan')

@section('content')
<div class="d-flex align-items-center mb-3 gap-3 flex-wrap">
    <div class="stat-label mb-0">Daftar tahun ajaran</div>
    <a class="btn btn-madani" href="{{ route('tahun-ajaran.create') }}">Tambah</a>
</div>
<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Tahun ajaran</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tahunAjarans as $item)
                    <tr>
                        <td>{{ $item->nama }}</td>
                        <td>{{ $item->labelStatus() }}</td>
                        <td class="text-end">
                            <div class="emis-aksi">
                                @unless ($item->adalahAktif())
                                    <form method="POST" action="{{ route('tahun-ajaran.aktifkan', $item) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">Aktifkan</button>
                                    </form>
                                @endunless
                                <a class="emis-aksi-btn" href="{{ route('tahun-ajaran.edit', $item) }}" title="Ubah">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if ($item->bisaDihapus())
                                    <form method="POST" action="{{ route('tahun-ajaran.destroy', $item) }}" data-confirm="Hapus tahun ajaran ini?" data-confirm-title="Hapus" data-loading-text="Menghapus…">
                                        @csrf
                                        @method('DELETE')
                                        <button class="emis-aksi-btn" type="submit" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-secondary p-3">Belum ada tahun ajaran.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
