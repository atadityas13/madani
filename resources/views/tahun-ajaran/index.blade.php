@extends('layouts.app')

@section('title', 'Tahun ajaran')
@section('heading', 'Tahun ajaran')
@section('subheading', 'Kelembagaan · semester aktif')

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
                    <th>Semester</th>
                    <th>Periode</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tahunAjarans as $item)
                    <tr>
                        <td>{{ $item->nama }}</td>
                        <td>{{ $item->labelSemester() }}</td>
                        <td>{{ $item->tanggal_mulai?->format('d/m/Y') }} – {{ $item->tanggal_selesai?->format('d/m/Y') }}</td>
                        <td>{{ $item->is_aktif ? 'Aktif' : 'Tidak aktif' }}</td>
                        <td class="text-end">
                            <div class="emis-aksi">
                                @unless ($item->is_aktif)
                                    <form method="POST" action="{{ route('tahun-ajaran.aktifkan', $item) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">Aktifkan</button>
                                    </form>
                                @endunless
                                <a class="emis-aksi-btn" href="{{ route('tahun-ajaran.edit', $item) }}" title="Ubah">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @unless ($item->is_aktif)
                                    <form method="POST" action="{{ route('tahun-ajaran.destroy', $item) }}" onsubmit="return confirm('Hapus tahun ajaran ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="emis-aksi-btn" type="submit" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-secondary p-3">Belum ada tahun ajaran.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
