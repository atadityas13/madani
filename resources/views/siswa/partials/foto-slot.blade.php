@php
    $portal = $portal ?? false;
    $fieldOnly = $fieldOnly ?? false;
    $fotoUrl = $fotoUrl ?? null;
    $inisial = $inisial ?? 'SW';
    $ukuran = $ukuran ?? 'head';
    $bisaKelola = (! $portal) && (! $fieldOnly) && isset($siswa) && auth()->user()?->can('update', $siswa);
@endphp

<div class="siswa-foto-slot siswa-foto-slot--{{ $ukuran }}" data-siswa-foto @if ($bisaKelola) data-siswa-foto-managed @endif>
    <div class="siswa-foto-slot__frame" @if ($bisaKelola || $fieldOnly) data-siswa-foto-preview role="button" tabindex="0" title="Pilih foto" @endif>
        @if ($fotoUrl)
            <img src="{{ $fotoUrl }}" alt="Foto {{ isset($siswa) ? $siswa->nama : 'siswa' }}">
        @else
            <span class="siswa-foto-slot__fallback" aria-hidden="true">{{ $inisial }}</span>
        @endif
    </div>

    @if ($fieldOnly)
        <input
            class="siswa-foto-slot__input"
            type="file"
            name="foto"
            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
            data-siswa-foto-input
        >
        <div class="siswa-foto-slot__hint">Opsional · maks. 500KB · rasio 3:4</div>
        @error('foto')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    @elseif ($bisaKelola)
        <form
            method="POST"
            action="{{ route('siswa.foto.upload', $siswa) }}"
            enctype="multipart/form-data"
            class="siswa-foto-slot__upload"
            data-siswa-foto-upload-form
            data-no-loading
        >
            @csrf
            <input
                class="siswa-foto-slot__input"
                type="file"
                name="foto"
                accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                data-siswa-foto-input
                required
            >
        </form>
        <div class="siswa-foto-slot__actions">
            <button type="button" class="btn btn-sm btn-madani" data-siswa-foto-pick>
                {{ $fotoUrl ? 'Ganti' : 'Unggah' }}
            </button>
            @if ($fotoUrl)
                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    data-dokumen-hapus
                    data-url="{{ route('siswa.foto.destroy', $siswa) }}"
                    data-judul="Foto Siswa"
                >
                    Hapus
                </button>
            @endif
        </div>
        <div class="siswa-foto-slot__hint">Maks. 500KB · jpg/png · rasio 3:4</div>
        @error('foto')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    @endif
</div>
