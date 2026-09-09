@extends('layouts.app')

@section('title', 'Persuratan')
@section('heading', 'Persuratan')
@section('subheading', 'Pilih jenis surat lalu unduh PDF')

@section('content')
<style>
    .surat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 0.75rem;
    }
    .surat-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 10px;
        background: #fff;
        overflow: hidden;
        cursor: pointer;
        transition: box-shadow .15s ease, transform .15s ease;
        text-align: left;
        padding: 0;
        width: 100%;
    }
    .surat-card:hover {
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
        transform: translateY(-1px);
    }
    .surat-card__preview {
        aspect-ratio: 210 / 297;
        background: #f8fafc;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        overflow: hidden;
        max-height: 180px;
    }
    .surat-card__preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: top center;
        display: block;
    }
    .surat-card__body {
        padding: 0.55rem 0.65rem 0.7rem;
    }
    .surat-card__title {
        font-weight: 700;
        font-size: 0.9rem;
        margin: 0 0 0.15rem;
        color: #0f172a;
        line-height: 1.25;
    }
    .surat-card__desc {
        margin: 0;
        font-size: 0.75rem;
        color: #64748b;
        line-height: 1.3;
    }
    .guru-dropdown {
        position: relative;
    }
    .guru-dropdown__toggle {
        width: 100%;
        text-align: left;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .guru-dropdown__menu {
        display: none;
        position: absolute;
        z-index: 20;
        left: 0;
        right: 0;
        top: calc(100% + 4px);
        max-height: 260px;
        overflow: auto;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 10px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
        padding: 0.5rem;
    }
    .guru-dropdown.is-open .guru-dropdown__menu {
        display: block;
    }
    .guru-dropdown__item {
        display: flex;
        gap: 0.55rem;
        align-items: flex-start;
        padding: 0.35rem 0.4rem;
        border-radius: 6px;
        cursor: pointer;
        margin: 0;
    }
    .guru-dropdown__item:hover {
        background: #f8fafc;
    }
    .guru-dropdown__item span {
        font-size: 0.9rem;
        line-height: 1.3;
    }
</style>

<div class="surat-grid">
    @foreach ($suratList as $surat)
        <button
            type="button"
            class="surat-card"
            data-bs-toggle="modal"
            data-bs-target="#modalGenerateSurat"
            data-surat-judul="{{ $surat['judul'] }}"
            data-surat-action="{{ $surat['generate_route'] }}"
        >
            <div class="surat-card__preview">
                <img src="{{ $surat['preview'] }}" alt="Preview {{ $surat['judul'] }}">
            </div>
            <div class="surat-card__body">
                <h3 class="surat-card__title">{{ $surat['judul'] }}</h3>
                <p class="surat-card__desc">{{ $surat['deskripsi'] }}</p>
            </div>
        </button>
    @endforeach
</div>

<div class="modal fade" id="modalGenerateSurat" tabindex="-1" aria-labelledby="modalGenerateSuratLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" id="formGenerateSurat" data-no-loading>
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="modalGenerateSuratLabel">Generate surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="tanggal_surat">Tanggal surat</label>
                    <input
                        class="form-control"
                        type="date"
                        name="tanggal_surat"
                        id="tanggal_surat"
                        value="{{ now()->format('Y-m-d') }}"
                        required
                    >
                </div>

                <label class="form-label">Pilih guru</label>
                <div class="guru-dropdown" id="guruDropdown" data-guru-dropdown>
                    <button class="btn btn-outline-secondary guru-dropdown__toggle" type="button" data-guru-toggle>
                        <span data-guru-label>Pilih guru…</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="guru-dropdown__menu">
                        <label class="guru-dropdown__item">
                            <input type="checkbox" value="__all__" data-guru-all>
                            <span><strong>Pilih semua</strong></span>
                        </label>
                        <hr class="my-2">
                        @forelse ($gtks as $gtk)
                            <label class="guru-dropdown__item">
                                <input type="checkbox" name="gtk_ids[]" value="{{ $gtk->id }}" data-guru-item>
                                <span>{{ $gtk->nama_lengkap }}@if($gtk->nuptk) <small class="text-secondary">({{ $gtk->nuptk }})</small>@endif</span>
                            </label>
                        @empty
                            <div class="text-secondary small px-1 py-2">Belum ada guru aktif.</div>
                        @endforelse
                    </div>
                </div>
                <div class="form-text mt-2" data-guru-count>0 guru dipilih</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-outline-secondary" id="btnUnduhSurat">Unduh PDF</button>
                <button type="button" class="btn btn-madani" id="btnCetakSurat">Cetak</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('modalGenerateSurat');
    const form = document.getElementById('formGenerateSurat');
    const title = document.getElementById('modalGenerateSuratLabel');
    const dropdown = document.querySelector('[data-guru-dropdown]');
    const toggle = document.querySelector('[data-guru-toggle]');
    const label = document.querySelector('[data-guru-label]');
    const countEl = document.querySelector('[data-guru-count]');
    const allBox = document.querySelector('[data-guru-all]');
    const btnCetak = document.getElementById('btnCetakSurat');
    const btnUnduh = document.getElementById('btnUnduhSurat');
    const items = () => Array.from(document.querySelectorAll('[data-guru-item]'));

    function syncLabel() {
        const checked = items().filter((el) => el.checked);
        const total = items().length;
        if (allBox) {
            allBox.checked = total > 0 && checked.length === total;
            allBox.indeterminate = checked.length > 0 && checked.length < total;
        }
        if (countEl) {
            countEl.textContent = `${checked.length} guru dipilih`;
        }
        if (!label) {
            return;
        }
        if (checked.length === 0) {
            label.textContent = 'Pilih guru…';
        } else if (checked.length === 1) {
            label.textContent = checked[0].closest('label')?.querySelector('span')?.childNodes[0]?.textContent?.trim() || '1 guru dipilih';
        } else if (checked.length === total) {
            label.textContent = `Semua guru (${total})`;
        } else {
            label.textContent = `${checked.length} guru dipilih`;
        }
    }

    function selectedCount() {
        return items().filter((el) => el.checked).length;
    }

    function closeModal() {
        if (!modal || typeof bootstrap === 'undefined') {
            return;
        }
        bootstrap.Modal.getOrCreateInstance(modal).hide();
    }

    function filenameFromDisposition(header, fallback) {
        if (!header) {
            return fallback;
        }
        const utf = header.match(/filename\*=UTF-8''([^;]+)/i);
        if (utf?.[1]) {
            try {
                return decodeURIComponent(utf[1]);
            } catch {
                // keep fallback parsing below
            }
        }
        const plain = header.match(/filename="?([^";]+)"?/i);
        return plain?.[1]?.trim() || fallback;
    }

    function validateGenerate() {
        if (!form) {
            return false;
        }
        if (selectedCount() === 0) {
            alert('Pilih minimal satu guru.');
            return false;
        }
        const tanggal = form.querySelector('#tanggal_surat');
        if (tanggal && !tanggal.value) {
            alert('Tanggal surat wajib diisi.');
            return false;
        }
        return true;
    }

    async function requestPdf(mode) {
        const data = new FormData(form);
        data.set('mode', mode);

        const response = await fetch(form.action, {
            method: 'POST',
            body: data,
            headers: {
                'Accept': 'application/pdf',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Gagal membuat PDF.');
        }

        const buffer = await response.arrayBuffer();
        const blob = new Blob([buffer], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);
        const filename = filenameFromDisposition(
            response.headers.get('content-disposition'),
            'SPTJM TPG.pdf'
        );

        return { url, filename };
    }

    document.querySelectorAll('.surat-card').forEach((card) => {
        card.addEventListener('click', () => {
            if (title) {
                title.textContent = card.getAttribute('data-surat-judul') || 'Generate surat';
            }
            if (form) {
                form.action = card.getAttribute('data-surat-action') || '';
            }
        });
    });

    toggle?.addEventListener('click', (e) => {
        e.preventDefault();
        dropdown?.classList.toggle('is-open');
    });

    document.addEventListener('click', (e) => {
        if (!dropdown?.contains(e.target)) {
            dropdown?.classList.remove('is-open');
        }
    });

    allBox?.addEventListener('change', () => {
        items().forEach((el) => { el.checked = allBox.checked; });
        syncLabel();
    });

    items().forEach((el) => el.addEventListener('change', syncLabel));

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
    });

    btnUnduh?.addEventListener('click', async () => {
        if (!validateGenerate()) {
            return;
        }

        btnUnduh.disabled = true;
        btnCetak && (btnCetak.disabled = true);
        const oldLabel = btnUnduh.textContent;
        btnUnduh.textContent = 'Menyiapkan…';

        try {
            const { url, filename } = await requestPdf('download');
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 60_000);
            closeModal();
        } catch (err) {
            alert(err?.message || 'Gagal mengunduh PDF.');
        } finally {
            btnUnduh.disabled = false;
            btnCetak && (btnCetak.disabled = false);
            btnUnduh.textContent = oldLabel || 'Unduh PDF';
        }
    });

    btnCetak?.addEventListener('click', async () => {
        if (!validateGenerate()) {
            return;
        }

        // Buka tab dulu (sync) supaya tidak diblokir popup blocker setelah await.
        const preview = window.open('about:blank', '_blank');
        if (!preview) {
            alert('Izinkan popup untuk membuka preview PDF.');
            return;
        }
        preview.document.write('<p style="font-family:sans-serif;padding:1rem">Menyiapkan PDF…</p>');

        btnCetak.disabled = true;
        btnUnduh && (btnUnduh.disabled = true);
        const oldLabel = btnCetak.textContent;
        btnCetak.textContent = 'Menyiapkan…';

        try {
            const { url } = await requestPdf('print');
            preview.location.href = url;
            setTimeout(() => URL.revokeObjectURL(url), 60_000);
            closeModal();
        } catch (err) {
            preview.close();
            alert(err?.message || 'Gagal membuka preview PDF.');
        } finally {
            btnCetak.disabled = false;
            btnUnduh && (btnUnduh.disabled = false);
            btnCetak.textContent = oldLabel || 'Cetak';
        }
    });

    modal?.addEventListener('hidden.bs.modal', () => {
        dropdown?.classList.remove('is-open');
    });

    syncLabel();
})();
</script>
@endsection
