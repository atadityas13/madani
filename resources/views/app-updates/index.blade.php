@extends('layouts.app')

@section('title', 'Update Aplikasi')
@section('heading', 'Update Aplikasi')
@section('subheading', 'Kebijakan update Ta\'lim Android')

@section('content')
@php
    $value = fn (string $key) => old($key, $item?->{$key} ?? $defaults[$key] ?? null);
    $isActive = old('is_active', $item?->is_active ?? $defaults['is_active']);
@endphp

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
    <div>
        <p class="text-secondary mb-0">
            Atur versi terbaru dan versi minimum yang boleh memakai aplikasi Android Ta'lim.
        </p>
    </div>
    <span class="badge text-bg-success align-self-center">Android</span>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <form action="{{ route('app-updates.store') }}" method="POST" class="madani-card p-4">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Latest version code</label>
                    <input class="form-control" type="number" name="latest_version_code" min="1" required
                        value="{{ $value('latest_version_code') }}">
                    @error('latest_version_code')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Latest version name</label>
                    <input class="form-control" type="text" name="latest_version_name" maxlength="40" required
                        value="{{ $value('latest_version_name') }}">
                    @error('latest_version_name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Minimum version code</label>
                    <input class="form-control" type="number" name="minimum_version_code" min="1" required
                        value="{{ $value('minimum_version_code') }}">
                    @error('minimum_version_code')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Judul</label>
                <input class="form-control" type="text" name="title" maxlength="160" required
                    value="{{ $value('title') }}">
                @error('title')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mt-3">
                <label class="form-label">Pesan</label>
                <textarea class="form-control" name="message" rows="3" maxlength="2000"
                    placeholder="Pesan yang tampil di aplikasi saat update tersedia.">{{ $value('message') }}</textarea>
                @error('message')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mt-3">
                <label class="form-label">Changelog</label>
                <textarea class="form-control" name="changelog" rows="5" maxlength="5000"
                    placeholder="- Perbaikan bug&#10;- Fitur baru">{{ $value('changelog') }}</textarea>
                @error('changelog')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mt-3">
                <label class="form-label">URL Play Store</label>
                <input class="form-control" type="url" name="play_store_url" maxlength="500"
                    value="{{ $value('play_store_url') }}">
                @error('play_store_url')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                    @checked($isActive)>
                <label class="form-check-label" for="is_active">
                    Aktifkan pengecekan update aplikasi
                </label>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button class="btn btn-madani" type="submit">Simpan kebijakan update</button>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="madani-card p-4 mb-3">
            <div class="text-uppercase small text-secondary fw-semibold mb-2">Cara kerja</div>
            <ul class="mb-0 small text-secondary ps-3">
                <li class="mb-2"><strong>Update opsional:</strong> version code app &lt; latest version code.</li>
                <li class="mb-2"><strong>Update wajib:</strong> version code app &lt; minimum version code.</li>
                <li>Tombol update membuka Google Play Store dari URL di sini.</li>
            </ul>
        </div>

        <div class="madani-card p-4">
            <div class="text-uppercase small text-secondary fw-semibold mb-2">Status saat ini</div>
            @if ($item)
                <dl class="mb-0 small">
                    <div class="d-flex justify-content-between gap-2 mb-2">
                        <dt class="text-secondary mb-0">Latest</dt>
                        <dd class="fw-semibold mb-0">{{ $item->latest_version_name }} ({{ $item->latest_version_code }})</dd>
                    </div>
                    <div class="d-flex justify-content-between gap-2 mb-2">
                        <dt class="text-secondary mb-0">Minimum</dt>
                        <dd class="fw-semibold mb-0">{{ $item->minimum_version_code }}</dd>
                    </div>
                    <div class="d-flex justify-content-between gap-2 mb-2">
                        <dt class="text-secondary mb-0">Status</dt>
                        <dd class="mb-0">
                            @if ($item->is_active)
                                <span class="badge text-bg-success">Aktif</span>
                            @else
                                <span class="badge text-bg-secondary">Nonaktif</span>
                            @endif
                        </dd>
                    </div>
                    <div class="d-flex justify-content-between gap-2">
                        <dt class="text-secondary mb-0">Diperbarui</dt>
                        <dd class="fw-semibold mb-0">
                            {{ optional($item->updated_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
                        </dd>
                    </div>
                </dl>
            @else
                <p class="mb-0 small text-secondary">Belum ada kebijakan update tersimpan.</p>
            @endif
        </div>
    </div>
</div>
@endsection
