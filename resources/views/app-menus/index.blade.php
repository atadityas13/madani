@extends('layouts.app')

@section('title', 'Menu Ta\'lim')
@section('heading', 'Menu Ta\'lim')
@section('subheading', 'Urutan menu bawaan & layanan WebView / link / app')

@section('content')
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
    <ul class="nav nav-pills mb-0">
        <li class="nav-item">
            <a class="nav-link {{ $audience === 'guru' ? 'active' : '' }}"
               href="{{ route('app-menus.index', ['audience' => 'guru']) }}">Guru</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $audience === 'siswa' ? 'active' : '' }}"
               href="{{ route('app-menus.index', ['audience' => 'siswa']) }}">Siswa</a>
        </li>
    </ul>
    @if ($audience === 'siswa' || $audience === 'guru')
        <button class="btn btn-madani" type="button" data-bs-toggle="modal" data-bs-target="#modalTambahMenu">
            Tambah menu
        </button>
    @endif
</div>

<p class="text-secondary small mb-3">
    Menu <strong>bawaan</strong> hanya bisa digeser urutannya. Menu <strong>custom</strong> bisa diedit (URL, ikon, mode buka, SSO Madani).
</p>

<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 5rem;">Urutan</th>
                    <th>Menu</th>
                    <th>Jenis</th>
                    <th>Mode</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <form method="POST" action="{{ route('app-menus.move-up', $item) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" type="submit" title="Naik">
                                        <i class="bi bi-chevron-up"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('app-menus.move-down', $item) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" type="submit" title="Turun">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($item->iconUrl())
                                    <img src="{{ $item->iconUrl() }}" alt="" width="36" height="36" class="rounded border" style="object-fit:contain;">
                                @else
                                    <span class="badge text-bg-light border">{{ $item->key ?? '—' }}</span>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $item->judul }}</div>
                                    @if ($item->url)
                                        <div class="text-secondary small text-truncate" style="max-width: 18rem;">{{ $item->url }}</div>
                                    @elseif ($item->package_name)
                                        <div class="text-secondary small">{{ $item->package_name }}</div>
                                    @endif
                                    @if ($item->requires_auth)
                                        <span class="badge text-bg-info">SSO Madani</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($item->isBuiltin())
                                <span class="badge text-bg-secondary">Bawaan</span>
                            @else
                                <span class="badge text-bg-primary">Custom</span>
                            @endif
                        </td>
                        <td class="small text-secondary">
                            {{ $item->open_mode ?? '—' }}
                        </td>
                        <td class="text-center">
                            @if ($item->isBuiltin() || $item->is_active)
                                <span class="badge text-bg-success">Aktif</span>
                            @else
                                <span class="badge text-bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @unless ($item->isBuiltin())
                                <div class="emis-aksi">
                                    <button class="emis-aksi-btn" type="button" title="Ubah"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalUbahMenu{{ $item->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('app-menus.destroy', $item) }}"
                                        data-confirm="Hapus menu ini?" data-confirm-title="Hapus">
                                        @csrf
                                        @method('DELETE')
                                        <button class="emis-aksi-btn" type="submit" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-secondary p-3">Belum ada menu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalTambahMenu" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('app-menus.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="audience" value="{{ $audience }}">
            <div class="modal-header">
                <h5 class="modal-title">Tambah menu {{ $audience }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('app-menus._form-fields', ['item' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-madani">Simpan</button>
            </div>
        </form>
    </div>
</div>

@foreach ($items->where('type', 'custom') as $item)
    <div class="modal fade" id="modalUbahMenu{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('app-menus.update', $item) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="audience" value="{{ $item->audience }}">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('app-menus._form-fields', ['item' => $item])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-madani">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection
