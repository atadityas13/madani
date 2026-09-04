@extends('layouts.app')

@section('title', 'Kalender Ta\'lim')
@section('heading', 'Kalender')
@section('subheading', 'Acara madrasah untuk aplikasi Ta\'lim')

@section('content')
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
    <p class="text-secondary mb-0">Acara aktif muncul di kalender seluruh guru di Ta'lim.</p>
    <button class="btn btn-madani" type="button" data-bs-toggle="modal" data-bs-target="#modalTambahKalender">
        Tambah acara
    </button>
</div>

<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Acara</th>
                    <th>Tanggal</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    @php
                        $timeValue = $item->event_time instanceof \DateTimeInterface
                            ? $item->event_time->format('H:i')
                            : substr((string) $item->event_time, 0, 5);
                    @endphp
                    <tr>
                        <td style="max-width: 22rem;">
                            <div class="fw-semibold">{{ $item->title }}</div>
                            @if ($item->note)
                                <div class="text-secondary small text-truncate">{{ $item->note }}</div>
                            @endif
                            @if ($item->is_important)
                                <span class="badge text-bg-warning mt-1">Penting</span>
                            @endif
                        </td>
                        <td class="text-nowrap text-secondary small">
                            {{ optional($item->event_date)->format('d M Y') }}
                            @if ($timeValue)
                                <div>Jam {{ str_replace(':', '.', $timeValue) }}</div>
                            @endif
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
                                    data-bs-target="#modalUbahKalender{{ $item->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('calendar-events.destroy', $item) }}"
                                    data-confirm="Hapus acara ini?" data-confirm-title="Hapus">
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
                        <td colspan="4" class="text-secondary p-3">Belum ada acara kalender.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach ($items as $item)
    @php
        $dateValue = optional($item->event_date)->format('Y-m-d');
        $timeValue = $item->event_time instanceof \DateTimeInterface
            ? $item->event_time->format('H:i')
            : substr((string) $item->event_time, 0, 5);
    @endphp
    <div class="modal fade" id="modalUbahKalender{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('calendar-events.update', $item) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Ubah acara</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Judul</label>
                        <input class="form-control" name="title" value="{{ $item->title }}" required maxlength="200">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal</label>
                            <input class="form-control" type="date" name="event_date" value="{{ $dateValue }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jam</label>
                            <input class="form-control" type="time" name="event_time" value="{{ $timeValue }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="note" rows="3" maxlength="2000">{{ $item->note }}</textarea>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_important" value="1" id="penting{{ $item->id }}" @checked($item->is_important)>
                        <label class="form-check-label" for="penting{{ $item->id }}">Tandai penting</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="aktif{{ $item->id }}" @checked($item->is_active)>
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

<div class="modal fade" id="modalTambahKalender" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('calendar-events.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah acara</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Judul</label>
                    <input class="form-control" name="title" value="{{ old('title') }}" required maxlength="200">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Tanggal</label>
                        <input class="form-control" type="date" name="event_date" value="{{ old('event_date', now('Asia/Jakarta')->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jam</label>
                        <input class="form-control" type="time" name="event_time" value="{{ old('event_time') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea class="form-control" name="note" rows="3" maxlength="2000">{{ old('note') }}</textarea>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_important" value="1" id="pentingBaru" @checked(old('is_important'))>
                    <label class="form-check-label" for="pentingBaru">Tandai penting</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="aktifBaru" @checked(old('is_active', true))>
                    <label class="form-check-label" for="aktifBaru">Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-madani">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
