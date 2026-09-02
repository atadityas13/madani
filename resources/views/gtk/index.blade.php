@extends('layouts.app')

@section('title', 'Guru dan Tendik')
@section('heading', 'Guru dan Tendik')
@section('subheading', 'Data GTK')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <form class="d-flex gap-2 flex-grow-1" method="GET" style="max-width: 420px;">
        <input class="form-control" type="search" name="q" value="{{ $q }}" placeholder="Cari nama, NIP, NUPTK">
        <button class="btn btn-outline-secondary" type="submit">Cari</button>
    </form>
    <a class="btn btn-madani" href="{{ route('gtk.create') }}">Tambah</a>
</div>
<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>NUPTK</th>
                    <th>JK</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gtks as $gtk)
                    <tr>
                        <td>{{ $gtk->nama }}</td>
                        <td>{{ $gtk->nip ?: '—' }}</td>
                        <td>{{ $gtk->nuptk ?: '—' }}</td>
                        <td>{{ $gtk->jenis_kelamin ?: '—' }}</td>
                        <td>{{ $gtk->status === 'aktif' ? 'Aktif' : 'Nonaktif' }}</td>
                        <td class="text-end">
                            <div class="emis-aksi">
                                <a class="emis-aksi-btn" href="{{ route('gtk.edit', $gtk) }}" title="Ubah">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('gtk.destroy', $gtk) }}" onsubmit="return confirm('Hapus GTK ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="emis-aksi-btn" type="submit" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-secondary p-3">Belum ada data GTK.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($gtks->hasPages())
        <div class="p-3">{{ $gtks->links() }}</div>
    @endif
</div>
@endsection
