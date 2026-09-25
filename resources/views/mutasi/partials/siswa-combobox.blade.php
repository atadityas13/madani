@php
    $prefix = $prefix ?? 'siswa';
    $siswaId = $siswaId ?? '';
    $siswaLabel = $siswaLabel ?? '';
    $invalid = $invalid ?? false;
@endphp
<div
    class="position-relative"
    data-siswa-combobox
    data-cari-url="{{ $cariSiswaUrl }}"
>
    <input type="hidden" name="siswa_id" value="{{ $siswaId }}" data-siswa-id data-mutasi-required>
    <input type="hidden" name="siswa_label" value="{{ $siswaLabel }}" data-siswa-label-store>
    <input
        class="form-control @if ($invalid) is-invalid @endif"
        type="search"
        id="{{ $prefix }}_siswa_cari"
        value="{{ $siswaLabel }}"
        placeholder="Ketik NISN atau nama…"
        autocomplete="off"
        data-siswa-search
        disabled
    >
    <div class="list-group position-absolute w-100 shadow-sm mt-1" data-siswa-results hidden style="z-index: 1080; max-height: 240px; overflow: auto;"></div>
</div>
