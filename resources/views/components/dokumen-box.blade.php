@props([
    'judul',
    'name',
    'jenis',
    'dokumen' => null,
    'siswa' => null,
    'required' => false,
    'hint' => 'Maks. 1MB · pdf / jpg / png',
])

@php
    $path = $dokumen?->path;
    $url = $path ? \App\Support\R2Url::temporary($path) : null;
    $isPdf = $path && str_ends_with(strtolower($path), '.pdf');
    $hasFile = filled($path);
    $inputId = 'dokumen-input-'.str_replace(['[', ']'], '-', $name).'-'.uniqid();
    $hapusUrl = ($siswa && $hasFile)
        ? route('siswa.dokumen.destroy', [$siswa, $jenis])
        : null;
@endphp

<div
    class="dokumen-box {{ $errors->has($name) ? 'is-invalid' : '' }}"
    data-dokumen-box
    @if ($hasFile) data-berkas-tersimpan @endif
    {{ $attributes }}
>
    <span class="dokumen-box__label">{{ $judul }}</span>

    <div class="dokumen-box__preview" data-dokumen-preview>
        @if ($url && ! $isPdf)
            <img src="{{ $url }}" alt="{{ $judul }}" data-dokumen-img>
        @elseif ($hasFile)
            <div class="dokumen-box__pdf" data-dokumen-pdf>
                <i class="bi bi-file-earmark-pdf"></i>
                <span>{{ $dokumen?->nama_asli ?: 'PDF tersimpan' }}</span>
            </div>
        @else
            <div class="dokumen-box__empty" data-dokumen-empty>
                <i class="bi bi-cloud-upload"></i>
                <span>Belum ada berkas</span>
            </div>
        @endif
    </div>

    <div class="dokumen-box__actions">
        <button type="button" class="btn btn-sm btn-madani" data-dokumen-pick>
            {{ $hasFile ? 'Unggah ulang' : 'Unggah' }}
        </button>
        @if ($hapusUrl)
            <button
                type="button"
                class="btn btn-sm btn-outline-danger"
                data-dokumen-hapus
                data-url="{{ $hapusUrl }}"
                data-judul="{{ $judul }}"
            >
                Hapus
            </button>
        @endif
    </div>

    <input
        id="{{ $inputId }}"
        class="dokumen-box__input"
        type="file"
        name="{{ $name }}"
        accept=".pdf,.jpg,.jpeg,.png"
        data-berkas
        data-dokumen-input
        @required($required && ! $hasFile)
    >

    @if ($hint)
        <div class="dokumen-box__hint">{{ $hint }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
