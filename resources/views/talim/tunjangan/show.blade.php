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
    .talim-id-grid{display:grid;grid-template-columns:1fr 1fr;gap:.55rem .75rem;font-size:.8rem}
    .talim-id-grid strong{display:block;color:#64748b;font-weight:600;font-size:.7rem;text-transform:uppercase;letter-spacing:.03em;margin-bottom:.1rem}
    /* WebView Android: label+input harus bisa diketuk langsung; jangan opacity:0 / display:none */
    .talim-upload-btn{
        position:relative;
        overflow:hidden;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        margin:0;
        cursor:pointer;
    }
    .talim-upload-btn input[type="file"]{
        position:absolute;
        left:0;
        top:0;
        width:100%;
        height:100%;
        opacity:0.011;
        font-size:64px;
        cursor:pointer;
    }
    .talim-upload-busy{
        position:fixed;
        inset:0;
        z-index:2000;
        background:rgba(15,23,42,.35);
        display:none;
        align-items:center;
        justify-content:center;
        color:#fff;
        font-weight:700;
    }
    .talim-upload-busy.is-on{display:flex}
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
                                <form
                                    method="POST"
                                    action="{{ $uploadAction }}"
                                    enctype="multipart/form-data"
                                    class="talim-upload-form m-0"
                                >
                                    @csrf
                                    <input type="hidden" name="periode" value="{{ $row['periode'] }}">
                                    <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaran?->id }}">
                                    <label class="btn btn-sm btn-madani talim-upload-btn">
                                        {{ $row['dokumen'] ? 'Ganti' : 'Unggah' }}
                                        <input
                                            type="file"
                                            name="file"
                                            accept="application/pdf"
                                            required
                                        >
                                    </label>
                                </form>
                            @else
                                <span class="talim-tag">Terkunci</span>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    <div class="talim-upload-busy" id="uploadBusy" aria-live="polite">Mengunggah…</div>

    <script>
        (() => {
            const busy = document.getElementById('uploadBusy');

            document.querySelectorAll('.talim-upload-form').forEach((form) => {
                const input = form.querySelector('input[type="file"]');
                if (! input) {
                    return;
                }

                input.addEventListener('change', () => {
                    if (! input.files || ! input.files.length) {
                        return;
                    }

                    const file = input.files[0];
                    const name = (file.name || '').toLowerCase();
                    const type = (file.type || '').toLowerCase();
                    if (type && type !== 'application/pdf' && ! name.endsWith('.pdf')) {
                        window.alert('Pilih file PDF.');
                        input.value = '';
                        return;
                    }

                    if (busy) {
                        busy.classList.add('is-on');
                    }

                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
            });
        })();
    </script>
@endif
@endsection
