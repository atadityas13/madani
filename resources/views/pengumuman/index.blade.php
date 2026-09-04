@extends('layouts.app')

@section('title', 'Pengumuman')
@section('heading', 'Pengumuman')
@section('subheading', 'Pengumuman untuk aplikasi Ta\'lim')

@section('content')
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
    <p class="text-secondary mb-0">Kirim pengumuman ke guru melalui aplikasi Ta'lim.</p>
    <button class="btn btn-madani" type="button" data-bs-toggle="modal" data-bs-target="#modalTambahPengumuman">
        Tulis pengumuman
    </button>
</div>

<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Isi</th>
                    <th>Tanggal</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td class="fw-semibold" style="max-width: 14rem;">{{ $item->judul }}</td>
                        <td class="text-secondary" style="max-width: 22rem;">
                            <div class="text-truncate">{{ $item->isi }}</div>
                        </td>
                        <td class="text-nowrap text-secondary small">
                            {{ optional($item->published_at ?? $item->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
                        </td>
                        <td class="text-center">
                            @if ($item->is_active)
                                <span class="badge text-bg-success">Aktif</span>
                            @else
                                <span class="badge text-bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="emis-aksi">
                                <button class="emis-aksi-btn" type="button" title="Ubah"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalUbahPengumuman{{ $item->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('pengumuman.destroy', $item) }}"
                                    data-confirm="Hapus pengumuman ini?" data-confirm-title="Hapus">
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
                    <tr>
                        <td colspan="5" class="text-secondary p-3">Belum ada pengumuman.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach ($items as $item)
    <div class="modal fade" id="modalUbahPengumuman{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('pengumuman.update', $item) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Ubah pengumuman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Judul</label>
                        <input class="form-control" type="text" name="judul" maxlength="200" required
                            value="{{ $item->judul }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Isi</label>
                        <textarea class="form-control" name="isi" rows="5" maxlength="5000" required>{{ $item->isi }}</textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="aktif{{ $item->id }}"
                            @checked($item->is_active)>
                        <label class="form-check-label" for="aktif{{ $item->id }}">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-madani">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

<div class="modal fade" id="modalTambahPengumuman" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('pengumuman.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tulis pengumuman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Judul</label>
                    <input class="form-control" type="text" name="judul" maxlength="200" required
                        placeholder="Contoh: Rapat koordinasi minggu ini">
                </div>
                <div class="mb-3">
                    <label class="form-label">Isi</label>
                    <textarea class="form-control" name="isi" rows="5" maxlength="5000" required
                        placeholder="Tuliskan detail pengumuman..."></textarea>
                </div>
                <input type="hidden" name="is_active" value="1">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-madani">Kirim</button>
            </div>
        </form>
    </div>
</div>
@endsection
