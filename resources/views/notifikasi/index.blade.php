@extends('layouts.app')

@section('title', 'Notifikasi')
@section('heading', 'Notifikasi')
@section('subheading', 'Push FCM Ta\'lim — gambar, audio, personalisasi, jadwal')

@section('content')
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
    <p class="text-secondary mb-0">
        Notifikasi → lonceng. Pengumuman → lonceng + section. Pengingat → section.
        Placeholder: <code>@{{nama}}</code> (diganti nama penerima saat dikirim).
    </p>
    <div class="d-flex gap-2">
        <a href="{{ route('notifikasi.media.index') }}" class="btn btn-outline-secondary">Pustaka media</a>
        <button class="btn btn-madani" type="button" data-bs-toggle="modal" data-bs-target="#modalTambahNotifikasi">
            Tulis notifikasi
        </button>
    </div>
</div>

@if ($scheduled->isNotEmpty())
    <div class="madani-card p-3 mb-3">
        <h2 class="h6 mb-2">Terjadwal (belum terkirim)</h2>
        <ul class="mb-0 small">
            @foreach ($scheduled as $s)
                <li>
                    <strong>{{ $s->judul }}</strong>
                    — {{ $s->scheduled_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
                </li>
            @endforeach
        </ul>
    </div>
@endif

<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Jenis</th>
                    <th>Audience</th>
                    <th>Media</th>
                    <th>Tanggal</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td class="fw-semibold" style="max-width: 14rem;">{{ $item->judul }}</td>
                        <td class="text-secondary small">{{ \App\Models\Notifikasi::jenisOptions()[$item->jenis] ?? $item->jenis }}</td>
                        <td class="text-secondary small">
                            {{ \App\Models\Notifikasi::audienceOptions()[$item->audience] ?? $item->audience }}
                            @if (! empty($item->audience_ids))
                                <span class="text-muted">({{ count($item->audience_ids) }})</span>
                            @endif
                        </td>
                        <td class="text-secondary small">
                            @if ($item->gambar_url) <i class="bi bi-image" title="Gambar"></i> @endif
                            @if ($item->audio_url) <i class="bi bi-music-note-beamed" title="Audio"></i> @endif
                            @if ($item->deep_link) <i class="bi bi-box-arrow-up-right" title="Deep-link"></i> @endif
                            @if ($item->scheduled_at && ! $item->sent_at)
                                <span class="badge text-bg-warning">jadwal</span>
                            @elseif ($item->sent_at)
                                <span class="badge text-bg-info">terkirim</span>
                            @endif
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
                                <form method="POST" action="{{ route('notifikasi.resend', $item) }}" class="d-inline">
                                    @csrf
                                    <button class="emis-aksi-btn" type="submit" title="Kirim ulang FCM">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </form>
                                <button class="emis-aksi-btn" type="button" title="Ubah"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalUbahNotifikasi{{ $item->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('notifikasi.destroy', $item) }}"
                                    data-confirm="Hapus notifikasi ini?" data-confirm-title="Hapus">
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
                        <td colspan="7" class="text-secondary p-3">Belum ada notifikasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach ($items as $item)
    <div class="modal fade" id="modalUbahNotifikasi{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <form class="modal-content" method="POST" action="{{ route('notifikasi.update', $item) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Ubah notifikasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('notifikasi._form', [
                        'item' => $item,
                        'gtks' => $gtks,
                        'rombels' => $rombels,
                        'siswas' => $siswas,
                        'mediaImages' => $mediaImages,
                        'mediaAudios' => $mediaAudios,
                    ])
                    <div class="form-check mt-2">
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

<div class="modal fade" id="modalTambahNotifikasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form class="modal-content" method="POST" action="{{ route('notifikasi.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tulis notifikasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('notifikasi._form', [
                    'item' => null,
                    'gtks' => $gtks,
                    'rombels' => $rombels,
                    'siswas' => $siswas,
                    'mediaImages' => $mediaImages,
                    'mediaAudios' => $mediaAudios,
                ])
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
