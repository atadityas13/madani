@extends('layouts.app')

@section('title', 'Pustaka Media Notifikasi')
@section('heading', 'Pustaka Media')
@section('subheading', 'Gambar dan audio untuk notifikasi Ta\'lim')

@section('content')
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
    <a href="{{ route('notifikasi.index') }}" class="btn btn-outline-secondary btn-sm">← Kembali ke notifikasi</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="madani-card p-3">
            <h2 class="h6 mb-3">Unggah media</h2>
            <form method="POST" action="{{ route('notifikasi.media.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-2">
                    <label class="form-label">Label</label>
                    <input class="form-control" type="text" name="label" required maxlength="200" value="{{ old('label') }}">
                </div>
                <div class="mb-2">
                    <label class="form-label">Jenis</label>
                    <select class="form-select" name="type" required>
                        @foreach (\App\Models\NotifMedia::typeOptions() as $value => $label)
                            <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">File</label>
                    <input class="form-control" type="file" name="file" required>
                </div>
                <button class="btn btn-madani" type="submit">Simpan</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="madani-card p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Jenis</th>
                            <th>Pratinjau</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="fw-semibold">{{ $item->label }}</td>
                                <td class="text-secondary small">{{ \App\Models\NotifMedia::typeOptions()[$item->type] ?? $item->type }}</td>
                                <td>
                                    @if ($item->type === 'image')
                                        <img src="{{ $item->url }}" alt="" style="height: 40px; border-radius: 6px;">
                                    @else
                                        <audio controls preload="none" style="height: 32px; max-width: 220px;">
                                            <source src="{{ $item->url }}">
                                        </audio>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('notifikasi.media.destroy', $item) }}"
                                        data-confirm="Hapus media ini?" data-confirm-title="Hapus">
                                        @csrf
                                        @method('DELETE')
                                        <button class="emis-aksi-btn" type="submit" title="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-secondary p-3">Belum ada media.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
