@extends('layouts.talim-webview')

@section('title', $labelJenis.' · Tunjangan')
@section('heading', $labelJenis)
@section('subheading', $deskripsiJenis ?: $gtk->nama_lengkap)

@section('content')
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
    <div class="fw-semibold mb-1">{{ $gtk->nama_lengkap }}</div>
    <div class="small">NIP: {{ $gtk->nip ?: '—' }} · Gol: {{ $gtk->golongan ?: '—' }}</div>
    <div class="small">{{ $gtk->status_pegawai ?: '—' }} · NUPTK: {{ $gtk->nuptk ?: '—' }}</div>
    <div class="small">NRG: {{ $gtk->nrg ?: '—' }}</div>
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
        <form method="GET" class="mb-3">
            <label class="form-label">Tahun pelajaran</label>
            <select class="form-select" name="tahun_ajaran_id" onchange="this.form.submit()">
                @foreach ($tahunAjarans as $ta)
                    <option value="{{ $ta->id }}" @selected((int) $tahunAjaran?->id === (int) $ta->id)>{{ $ta->nama }}</option>
                @endforeach
            </select>
        </form>

        @foreach ($rows as $row)
            @if (($row['type'] ?? 'item') === 'header')
                <div class="small fw-semibold text-secondary mt-3 mb-2">{{ $row['label'] }}</div>
            @else
                <div class="talim-incomplete">
                    <div class="talim-incomplete__nama">
                        <div class="fw-semibold">{{ $row['label'] }}</div>
                        @if ($row['dokumen'])
                            <div class="small text-secondary">{{ $row['dokumen']->nama_asli ?: 'PDF tersimpan' }}</div>
                        @else
                            <div class="small text-secondary">Belum ada</div>
                        @endif
                    </div>
                    <div class="talim-incomplete__tags mt-2 w-100 gap-1">
                        @if ($row['dokumen'])
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal"
                                data-bs-target="#modalPreviewPdf"
                                data-preview-title="{{ $row['label'] }}"
                                data-preview-url="{{ route('talim.tunjangan.stream', [$jenis, $row['dokumen']]) }}"
                                data-download-url="{{ route('talim.tunjangan.download', [$jenis, $row['dokumen']]) }}"
                            >Lihat</button>
                        @endif
                        @if ($row['boleh_upload'])
                            <button
                                type="button"
                                class="btn btn-sm btn-madani"
                                data-bs-toggle="modal"
                                data-bs-target="#modalUploadPdf"
                                data-upload-periode="{{ $row['periode'] }}"
                                data-upload-label="{{ $row['label'] }}"
                                data-upload-mode="{{ $row['dokumen'] ? 'Ganti' : 'Unggah' }}"
                            >{{ $row['dokumen'] ? 'Ganti' : 'Unggah' }}</button>
                        @else
                            <span class="talim-tag">Terkunci</span>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="modal fade" id="modalUploadPdf" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ $uploadAction }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="periode" id="uploadPeriode">
                <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaran?->id }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUploadPdfLabel">Unggah PDF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-secondary mb-2" id="uploadPeriodeLabel"></p>
                    <label class="form-label" for="uploadFile">File PDF (maks. 2 MB)</label>
                    <input class="form-control" type="file" name="file" id="uploadFile" accept=".pdf,application/pdf" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-madani" id="uploadSubmitBtn">Unggah</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalPreviewPdf" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down modal-xl modal-dialog-centered">
            <div class="modal-content" style="min-height: 75vh;">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPreviewPdfLabel">Preview PDF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-0" style="height: 65vh;">
                    <iframe id="previewPdfFrame" title="Preview PDF" src="about:blank" style="width:100%;height:100%;border:0;"></iframe>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-outline-secondary" id="previewDownloadBtn" href="#">Unduh</a>
                    <button type="button" class="btn btn-madani" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const uploadModal = document.getElementById('modalUploadPdf');
            uploadModal?.addEventListener('show.bs.modal', (event) => {
                const btn = event.relatedTarget;
                if (!btn) return;
                document.getElementById('uploadPeriode').value = btn.getAttribute('data-upload-periode') || '';
                document.getElementById('uploadPeriodeLabel').textContent = btn.getAttribute('data-upload-label') || '';
                document.getElementById('uploadSubmitBtn').textContent = btn.getAttribute('data-upload-mode') || 'Unggah';
                document.getElementById('modalUploadPdfLabel').textContent = (btn.getAttribute('data-upload-mode') || 'Unggah') + ' PDF';
                const file = document.getElementById('uploadFile');
                if (file) file.value = '';
            });

            const previewModal = document.getElementById('modalPreviewPdf');
            const frame = document.getElementById('previewPdfFrame');
            const downloadBtn = document.getElementById('previewDownloadBtn');
            previewModal?.addEventListener('show.bs.modal', (event) => {
                const btn = event.relatedTarget;
                if (!btn) return;
                document.getElementById('modalPreviewPdfLabel').textContent = btn.getAttribute('data-preview-title') || 'Preview PDF';
                frame.src = btn.getAttribute('data-preview-url') || 'about:blank';
                downloadBtn.href = btn.getAttribute('data-download-url') || '#';
            });
            previewModal?.addEventListener('hidden.bs.modal', () => {
                frame.src = 'about:blank';
            });
        })();
    </script>
@endif
@endsection
