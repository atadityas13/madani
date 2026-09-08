@props([
    'ok' => false,
    'title' => null,
    'preview' => null,
    'label' => null,
    'icon' => 'check',
])

@php
    $iconClass = match ($icon) {
        'image' => 'bi-image',
        'doc' => 'bi-file-earmark-text',
        'card' => 'bi-person-vcard',
        default => 'bi-check-lg',
    };
@endphp

@if ($ok && is_array($preview) && filled($preview['preview_url'] ?? null))
    <button
        type="button"
        class="monitoring-flag is-ok is-clickable"
        title="{{ $title ?? 'Lihat preview' }}"
        data-monitoring-preview
        data-label="{{ $label ?? 'Preview' }}"
        data-preview-url="{{ $preview['preview_url'] }}"
        data-download-url="{{ $preview['download_url'] ?? $preview['preview_url'] }}"
        data-is-pdf="{{ ! empty($preview['is_pdf']) ? '1' : '0' }}"
        data-external="{{ ! empty($preview['external']) ? '1' : '0' }}"
    >
        <i class="bi {{ $iconClass }}" aria-hidden="true"></i>
        <span class="visually-hidden">Ya</span>
    </button>
@elseif ($ok)
    <span class="monitoring-flag is-ok" title="{{ $title ?? 'Lengkap' }}">
        <i class="bi bi-check-lg" aria-hidden="true"></i>
        <span class="visually-hidden">Ya</span>
    </span>
@else
    <span class="monitoring-flag is-no" title="{{ $title ?? 'Belum' }}">
        <i class="bi bi-x-lg" aria-hidden="true"></i>
        <span class="visually-hidden">Tidak</span>
    </span>
@endif
