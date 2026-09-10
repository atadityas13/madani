@extends('layouts.talim-webview')

@section('title', $labelJenis.' · Tunjangan')
@section('heading', $labelJenis)
@section('subheading', $deskripsiJenis ?: $gtk->nama_lengkap)

@section('content')
<style>
    .talim-period-list{display:flex;flex-direction:column}
    .talim-period-group{margin:.85rem 0 .35rem;font-size:.75rem;font-weight:700;letter-spacing:.02em;text-transform:uppercase;color:#64748b}
    .talim-period-row{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.8rem 0;border-bottom:1px solid #ececec}
    .talim-period-row:last-child{border-bottom:0}
    .talim-period-row__meta{min-width:0;flex:1}
    .talim-period-row__title{font-weight:700;color:#0f172a;font-size:.95rem}
    .talim-period-row__file{margin-top:.15rem;font-size:.75rem;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .talim-period-row__actions{display:flex;flex-shrink:0;gap:.4rem;align-items:center}
    .talim-sheet-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:1040;display:none}
    .talim-sheet-backdrop.is-open{display:block}
    .talim-sheet{position:fixed;left:0;right:0;bottom:0;z-index:1050;background:#fff;border-radius:1rem 1rem 0 0;padding:1rem 1rem calc(1rem + env(safe-area-inset-bottom,0px));max-width:40rem;margin:0 auto;box-shadow:0 -8px 28px rgba(15,23,42,.18);transform:translateY(110%);transition:transform .2s ease}
    .talim-sheet.is-open{transform:translateY(0)}
    .talim-sheet__title{font-weight:800;font-size:1.05rem;margin:0 0 .25rem}
    .talim-sheet__sub{color:#64748b;font-size:.85rem;margin-bottom:.9rem}
    .talim-file-pick{position:relative;display:flex;align-items:center;justify-content:center;min-height:3.25rem;border:1.5px dashed #cbd5e1;border-radius:.75rem;background:#f8fafc;color:#334155;font-weight:600;margin-bottom:.85rem;overflow:hidden}
    .talim-file-pick input[type=file]{position:absolute;inset:0;opacity:0;width:100%;height:100%;cursor:pointer;font-size:1.25rem}
    .talim-file-pick__name{font-size:.85rem;color:#64748b;margin-bottom:.85rem;word-break:break-all;min-height:1.2em}
    .talim-sheet__actions{display:grid;grid-template-columns:1fr 1fr;gap:.55rem}
    .talim-id-grid{display:grid;grid-template-columns:1fr 1fr;gap:.55rem .75rem;font-size:.8rem}
    .talim-id-grid strong{display:block;color:#64748b;font-weight:600;font-size:.7rem;text-transform:uppercase;letter-spacing:.03em;margin-bottom:.1rem}
</style>

@if (session('status'))
    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
@endif
@error('file')
    <div class="alert alert-danger py-2 small">{{ $message }}</div>
@enderror

<div class="talim-toolbar mb-3">
    <a class="talim-back" href="{{ route('talim.tunjangan.index') }}">← Kembali</a>
</div>

<div class="talim-panel mb-3">
    <div class="small text-secondary mb-2">Identitas</div>
    <div class="fw-semibold mb-2">{{ $gtk->nama_lengkap }}</div>
    <div class="talim-id-grid">
        <div><strong>NIP</strong>{{ $gtk->nip ?: '—' }}</div>
        <div><strong>Golongan</strong>{{ $gtk->golongan ?: '—' }}</div>
        <div><strong>Status</strong>{{ $gtk->status_pegawai ?: '—' }}</div>
        <div><strong>NRG</strong>{{ $gtk->nrg ?: '—' }}</div>
        <div style="grid-column:1/-1"><strong>NUPTK / PegID</strong>{{ $gtk->nuptk ?: '—' }}</div>
    </div>
</div>

@if ($jenis === 'sptjm')
    <div class="talim-panel">
        <div class="talim-section__title mb-2">Unduh SPTJM</div>
        <p class="small text-secondary mb-3">Surat dihasilkan otomatis dari data guru.</p>
        <form method="POST" action="{{ route('talim.tunjangan.sptjm') }}">
            @csrf
            <label class="form-label" for="tanggal_surat">Tanggal surat</label>
            <input class="form-control mb-3" type="date" name="tanggal_surat" id="tanggal_surat" value="{{ now()->format('Y-m-d') }}" required>
            <div class="d-grid gap-2">
                <button class="btn btn-outline-secondary" type="submit" name="mode" value="download">Unduh PDF</button>
                <button class="btn btn-madani" type="submit" name="mode" value="print" formtarget="_blank">Cetak / Preview</button>
            </div>
        </form>
    </div>
@else
    <div class="talim-panel mb-3">
        <form method="GET" class="mb-2">
            <label class="form-label">Tahun pelajaran</label>
            <select class="form-select" name="tahun_ajaran_id" onchange="this.form.submit()">
                @foreach ($tahunAjarans as $ta)
                    <option value="{{ $ta->id }}" @selected((int) $tahunAjaran?->id === (int) $ta->id)>{{ $ta->nama }}</option>
                @endforeach
            </select>
        </form>

        <div class="talim-period-list">
            @foreach ($rows as $row)
                @if (($row['type'] ?? 'item') === 'header')
                    <div class="talim-period-group">{{ $row['label'] }}</div>
                @else
                    <div class="talim-period-row">
                        <div class="talim-period-row__meta">
                            <div class="talim-period-row__title">{{ $row['label'] }}</div>
                            <div class="talim-period-row__file">
                                @if ($row['dokumen'])
                                    {{ $row['dokumen']->nama_asli ?: 'PDF tersimpan' }}
                                @else
                                    Belum ada
                                @endif
                            </div>
                        </div>
                        <div class="talim-period-row__actions">
                            @if ($row['dokumen'])
                                <a
                                    class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('talim.tunjangan.preview', [$jenis, $row['dokumen']]) }}"
                                >Lihat</a>
                            @endif
                            @if ($row['boleh_upload'])
                                <button
                                    type="button"
                                    class="btn btn-sm btn-madani"
                                    data-open-upload
                                    data-periode="{{ $row['periode'] }}"
                                    data-label="{{ $row['label'] }}"
                                    data-mode="{{ $row['dokumen'] ? 'Ganti' : 'Unggah' }}"
                                >{{ $row['dokumen'] ? 'Ganti' : 'Unggah' }}</button>
                            @else
                                <span class="talim-tag">Terkunci</span>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    <div class="talim-sheet-backdrop" id="uploadBackdrop" hidden></div>
    <div class="talim-sheet" id="uploadSheet" hidden aria-hidden="true">
        <form method="POST" action="{{ $uploadAction }}" enctype="multipart/form-data" id="uploadForm">
            @csrf
            <input type="hidden" name="periode" id="uploadPeriode">
            <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaran?->id }}">
            <div class="talim-sheet__title" id="uploadTitle">Unggah PDF</div>
            <div class="talim-sheet__sub" id="uploadLabel"></div>
            <label class="talim-file-pick">
                <input type="file" name="file" id="uploadFile" accept="application/pdf,.pdf" required>
                <span id="uploadPickText">Ketuk untuk pilih PDF</span>
            </label>
            <div class="talim-file-pick__name" id="uploadFileName">Belum ada file dipilih</div>
            <div class="talim-sheet__actions">
                <button type="button" class="btn btn-outline-secondary" id="uploadCancel">Batal</button>
                <button type="submit" class="btn btn-madani" id="uploadSubmit" disabled>Kirim</button>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const backdrop = document.getElementById('uploadBackdrop');
            const sheet = document.getElementById('uploadSheet');
            const periodeInput = document.getElementById('uploadPeriode');
            const titleEl = document.getElementById('uploadTitle');
            const labelEl = document.getElementById('uploadLabel');
            const fileInput = document.getElementById('uploadFile');
            const fileNameEl = document.getElementById('uploadFileName');
            const pickText = document.getElementById('uploadPickText');
            const submitBtn = document.getElementById('uploadSubmit');
            const cancelBtn = document.getElementById('uploadCancel');

            const openSheet = (btn) => {
                periodeInput.value = btn.getAttribute('data-periode') || '';
                const mode = btn.getAttribute('data-mode') || 'Unggah';
                titleEl.textContent = mode + ' PDF';
                labelEl.textContent = btn.getAttribute('data-label') || '';
                fileInput.value = '';
                fileNameEl.textContent = 'Belum ada file dipilih';
                pickText.textContent = 'Ketuk untuk pilih PDF';
                submitBtn.disabled = true;
                submitBtn.textContent = mode === 'Ganti' ? 'Ganti' : 'Unggah';
                backdrop.hidden = false;
                sheet.hidden = false;
                backdrop.classList.add('is-open');
                sheet.classList.add('is-open');
                sheet.setAttribute('aria-hidden', 'false');
            };

            const closeSheet = () => {
                sheet.classList.remove('is-open');
                backdrop.classList.remove('is-open');
                sheet.setAttribute('aria-hidden', 'true');
                setTimeout(() => {
                    sheet.hidden = true;
                    backdrop.hidden = true;
                }, 200);
            };

            document.querySelectorAll('[data-open-upload]').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    openSheet(btn);
                });
            });

            fileInput.addEventListener('change', () => {
                const file = fileInput.files && fileInput.files[0];
                if (!file) {
                    fileNameEl.textContent = 'Belum ada file dipilih';
                    pickText.textContent = 'Ketuk untuk pilih PDF';
                    submitBtn.disabled = true;
                    return;
                }
                fileNameEl.textContent = file.name;
                pickText.textContent = 'Ganti pilihan file';
                submitBtn.disabled = false;
            });

            cancelBtn.addEventListener('click', closeSheet);
            backdrop.addEventListener('click', closeSheet);
        })();
    </script>
@endif
@endsection
