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
    $previewUrl = is_array($preview) ? ($preview['preview_url'] ?? null) : null;
    $downloadUrl = is_array($preview) ? ($preview['download_url'] ?? $previewUrl) : null;
    $isExternal = is_array($preview) && ! empty($preview['external']);
    $isPdf = is_array($preview) && ! empty($preview['is_pdf']);
@endphp

@if ($ok && filled($previewUrl) && $isExternal)
    <a
        href="{{ $previewUrl }}"
        class="monitoring-flag is-ok is-clickable"
        title="{{ $title ?? 'Buka' }}"
        target="_blank"
        rel="noopener noreferrer"
    >
        <i class="bi {{ $iconClass }}" aria-hidden="true"></i>
        <span class="visually-hidden">{{ $label ?? 'Buka' }}</span>
    </a>
@elseif ($ok && filled($previewUrl))
    <button
        type="button"
        class="monitoring-flag is-ok is-clickable"
        title="{{ $title ?? 'Lihat preview' }}"
        data-monitoring-preview
        data-label="{{ $label ?? 'Preview' }}"
        data-preview-url="{{ $previewUrl }}"
        data-download-url="{{ $downloadUrl }}"
        data-is-pdf="{{ $isPdf ? '1' : '0' }}"
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
