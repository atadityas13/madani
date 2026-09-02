@extends('layouts.app')

@section('title', 'Pengguna')
@section('heading', 'Pengguna')
@section('subheading', 'Manajemen akun')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <form class="d-flex gap-2 flex-grow-1" method="GET" style="max-width: 420px;">
        <input class="form-control" type="search" name="q" value="{{ $q }}" placeholder="Cari nama, username, email">
        <button class="btn btn-outline-secondary" type="submit">Cari</button>
    </form>
    <a class="btn btn-madani" href="{{ route('pengguna.create') }}">Tambah</a>
</div>
<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Peran</th>
                    <th>GTK / wali</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->username }}</td>
                        <td>{{ $item->labelPeran() }}</td>
                        <td>{{ $item->gtk?->nama ?: '—' }}</td>
                        <td>{{ $item->is_aktif ? 'Aktif' : 'Nonaktif' }}</td>
                        <td class="text-end">
                            <div class="emis-aksi">
                                <a class="emis-aksi-btn" href="{{ route('pengguna.edit', $item) }}" title="Ubah">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @can('delete', $item)
                                    <form method="POST" action="{{ route('pengguna.destroy', $item) }}" onsubmit="return confirm('Hapus pengguna ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="emis-aksi-btn" type="submit" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-secondary p-3">Belum ada pengguna.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($users->hasPages())
        <div class="p-3">{{ $users->links() }}</div>
    @endif
</div>
@endsection
