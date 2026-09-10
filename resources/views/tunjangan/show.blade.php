@extends('layouts.app')

@section('title', $labelJenis.' · '.$gtk->nama)
@section('heading', $labelJenis)
@section('subheading', $deskripsiJenis ?: $gtk->nama_lengkap)

@section('content')
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@error('file')
    <div class="alert alert-danger">{{ $message }}</div>
@enderror

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="{{ $isAdmin ? route('tunjangan.jenis.index', $jenis) : route('tunjangan.index') }}">Kembali</a>
</div>

<div class="madani-card p-3 mb-3">
    <div class="stat-label mb-2">Identitas guru</div>
    <div class="row g-2 small">
        <div class="col-md-6"><strong>Nama</strong><div>{{ $gtk->nama_lengkap }}</div></div>
        <div class="col-md-3"><strong>NIP</strong><div>{{ $gtk->nip ?: '—' }}</div></div>
        <div class="col-md-3"><strong>Golongan</strong><div>{{ $gtk->golongan ?: '—' }}</div></div>
        <div class="col-md-3"><strong>Status</strong><div>{{ $gtk->status_pegawai ?: '—' }}</div></div>
        <div class="col-md-3"><strong>NUPTK / PegID</strong><div>{{ $gtk->nuptk ?: '—' }}</div></div>
        <div class="col-md-3"><strong>NRG</strong><div>{{ $gtk->nrg ?: '—' }}</div></div>
    </div>
</div>

@if ($jenis === 'sptjm')
    <div class="madani-card p-3">
        <div class="stat-label mb-2">Unduh SPTJM</div>
        <p class="small text-secondary mb-3">Surat dihasilkan otomatis dari data guru (bukan upload berkas).</p>
        <form class="row g-2 align-items-end" method="POST" action="{{ route('tunjangan.sptjm.download', $gtk) }}">
            @csrf
            <div class="col-md-4">
                <label class="form-label" for="tanggal_surat">Tanggal surat</label>
                <input class="form-control" type="date" name="tanggal_surat" id="tanggal_surat" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="col-md-8 d-flex gap-2">
                <button class="btn btn-outline-secondary" type="submit" name="mode" value="download">Unduh PDF</button>
                <button class="btn btn-madani" type="submit" name="mode" value="print" formtarget="_blank">Cetak / Preview</button>
            </div>
        </form>
    </div>
@else
    <div class="madani-card p-3 mb-3">
        <form method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-md-4">
                <label class="form-label">Tahun pelajaran</label>
                <select class="form-select" name="tahun_ajaran_id" onchange="this.form.submit()">
                    @foreach ($tahunAjarans as $ta)
                        <option value="{{ $ta->id }}" @selected((int) $tahunAjaran?->id === (int) $ta->id)>{{ $ta->nama }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 3.5rem;">No</th>
                        <th>{{ $jenis === 'skakpt' ? 'Bulan' : 'Semester' }}</th>
                        <th>Berkas</th>
                        <th class="text-end" style="width: 12rem;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php $no = 0; @endphp
                    @foreach ($rows as $row)
                        @if (($row['type'] ?? 'item') === 'header')
                            <tr class="table-light">
                                <td colspan="4" class="fw-semibold small text-secondary">{{ $row['label'] }}</td>
                            </tr>
                        @else
                            @php $no++; @endphp
                            <tr>
                                <td>{{ $no }}</td>
                                <td>{{ $row['label'] }}</td>
                                <td>
                                    @if ($row['dokumen'])
                                        <button
                                            type="button"
                                            class="btn btn-link p-0 text-start"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalPreviewPdf"
                                            data-preview-title="{{ $row['label'] }}"
                                            data-preview-url="{{ route('tunjangan.jenis.stream', [$jenis, $gtk, $row['dokumen']]) }}"
                                            data-download-url="{{ route('tunjangan.jenis.download', [$jenis, $gtk, $row['dokumen']]) }}"
                                        >
                                            {{ $row['dokumen']->nama_asli ?: 'PDF tersimpan' }}
                                        </button>
                                    @else
                                        <span class="text-secondary">Belum ada</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                        @if ($row['dokumen'])
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalPreviewPdf"
                                                data-preview-title="{{ $row['label'] }}"
                                                data-preview-url="{{ route('tunjangan.jenis.stream', [$jenis, $gtk, $row['dokumen']]) }}"
                                                data-download-url="{{ route('tunjangan.jenis.download', [$jenis, $gtk, $row['dokumen']]) }}"
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
                                            <span class="badge text-bg-light text-secondary">Terkunci</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalUploadPdf" tabindex="-1" aria-labelledby="modalUploadPdfLabel" aria-hidden="true">
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

    <div class="modal fade" id="modalPreviewPdf" tabindex="-1" aria-labelledby="modalPreviewPdfLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-lg-down">
            <div class="modal-content" style="min-height: 80vh;">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPreviewPdfLabel">Preview PDF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-0" style="height: 70vh;">
                    <iframe id="previewPdfFrame" title="Preview PDF" src="about:blank" style="width:100%;height:100%;border:0;"></iframe>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-outline-secondary" id="previewDownloadBtn" href="#" download>Unduh</a>
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
