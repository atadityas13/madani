@props([
    'judul',
    'jenis',
    'dokumen' => null,
    'siswa',
])

@php
    $path = $dokumen?->path;
    $url = $path ? \App\Support\R2Url::temporary($path) : null;
    $isPdf = $path && str_ends_with(strtolower((string) $path), '.pdf');
    $hasFile = filled($path);
    $downloadUrl = $hasFile
        ? route('siswa.dokumen.download', [$siswa, $jenis])
        : null;
@endphp

<div class="siswa-detail__dokumen">
    <div class="siswa-detail__dokumen-label">{{ $judul }}</div>
    @if ($downloadUrl)
        <a class="siswa-detail__dokumen-preview" href="{{ $downloadUrl }}" title="Unduh {{ $judul }}">
            @if ($url && ! $isPdf)
                <img src="{{ $url }}" alt="{{ $judul }}">
            @else
                <span class="siswa-detail__dokumen-pdf">
                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                    <span>PDF</span>
                </span>
            @endif
        </a>
    @else
        <div class="siswa-detail__dokumen-preview is-empty">
            <span>—</span>
        </div>
    @endif
</div>
