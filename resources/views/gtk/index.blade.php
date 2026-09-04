@extends('layouts.app')

@section('title', 'Guru dan Tendik')
@section('heading', 'Guru dan Tendik')
@section('subheading', 'Data GTK')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <form class="d-flex gap-2 flex-grow-1 flex-wrap" method="GET" style="max-width: 640px;">
        <input class="form-control" type="search" name="q" value="{{ $q }}" placeholder="Cari nama, NIP, NUPTK" style="min-width: 220px;">
        <select class="form-select" name="jenis" style="max-width: 180px;">
            <option value="">Semua jenis</option>
            @foreach (\App\Models\Gtk::jenisOptions() as $value => $label)
                <option value="{{ $value }}" @selected($jenis === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-secondary" type="submit">Cari</button>
    </form>
    <a class="btn btn-madani" href="{{ route('gtk.create') }}">Tambah</a>
</div>
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Jenis</th>
                    <th>NIP</th>
                    <th>NUPTK</th>
                    <th>JK</th>
                    <th>Status</th>
                    <th>Akun Ta'lim</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gtks as $gtk)
                    <tr>
                        <td>{{ $gtk->nama_lengkap }}</td>
                        <td>{{ \App\Models\Gtk::jenisOptions()[$gtk->jenis] ?? $gtk->jenis }}</td>
                        <td>{{ $gtk->nip ?: '—' }}</td>
                        <td>{{ $gtk->nuptk ?: '—' }}</td>
                        <td>{{ $gtk->jenis_kelamin ?: '—' }}</td>
                        <td>{{ $gtk->status === 'aktif' ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>
                            @if ($gtk->akun)
                                <span class="text-success">{{ $gtk->akun->username }}</span>
                                @if (! $gtk->akun->is_aktif)
                                    <span class="text-secondary">(nonaktif)</span>
                                @endif
                            @else
                                <span class="text-secondary">Belum ada</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="emis-aksi">
                                @can('create', \App\Models\User::class)
                                    @if ($gtk->akun)
                                        <form method="POST" action="{{ route('gtk.akun.reset', $gtk) }}" data-confirm="Reset password akun ini ke NIP?" data-confirm-title="Reset password" data-loading-text="Mereset…">
                                            @csrf
                                            <button class="emis-aksi-btn" type="submit" title="Reset password">
                                                <i class="bi bi-key"></i>
                                            </button>
                                        </form>
                                    @elseif (filled($gtk->nip))
                                        <form method="POST" action="{{ route('gtk.akun.store', $gtk) }}" data-confirm="Buat akun Ta'lim dengan username = NIP?" data-confirm-title="Buat akun" data-loading-text="Membuat…">
                                            @csrf
                                            <button class="emis-aksi-btn" type="submit" title="Buat akun Ta'lim">
                                                <i class="bi bi-person-plus"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                                <a class="emis-aksi-btn" href="{{ route('gtk.edit', $gtk) }}" title="Ubah">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('gtk.destroy', $gtk) }}" data-confirm="Hapus GTK ini?" data-confirm-title="Hapus" data-loading-text="Menghapus…">
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
                    <tr><td colspan="8" class="text-secondary p-3">Belum ada data GTK.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($gtks->hasPages())
        <div class="p-3">{{ $gtks->links() }}</div>
    @endif
</div>
@endsection
