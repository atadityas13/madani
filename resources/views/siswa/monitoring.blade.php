@extends('layouts.app')

@section('title', 'Monitoring siswa')
@section('heading', 'Monitoring siswa')
@section('subheading', 'Status login, kelengkapan, dokumen, dan pernyataan')

@section('content')
@php
    $variabelLabels = [
        'login' => 'Login',
        'fcm' => 'FCM',
        'password' => 'Password diganti',
        'data-siswa' => 'Identitas',
        'orang-tua' => 'Orang tua',
        'alamat' => 'Alamat',
        'rekam-didik' => 'Rekam didik',
        'foto' => 'Foto',
        'kk' => 'KK',
        'akta_lahir' => 'Akta',
        'kip' => 'KIP',
        'kks' => 'KKS',
        'pkh' => 'PKH',
        'ijazah_sd' => 'Ijazah SD',
        'pernyataan' => 'Pernyataan',
        'kartu' => 'Kartu e-pelajar',
        'pengajuan_pending' => 'Ada pengajuan pending',
        'nis' => 'NIS terisi',
    ];
@endphp

<style>
    .monitoring-flag {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.5rem;
        height: 1.5rem;
        border: 0;
        background: transparent;
        padding: 0;
        line-height: 1;
    }
    .monitoring-flag.is-ok { color: #198754; }
    .monitoring-flag.is-ok.is-clickable { cursor: pointer; }
    .monitoring-flag.is-ok.is-clickable:hover { color: #146c43; }
    .monitoring-flag.is-no { color: #dc3545; cursor: default; }
    .monitoring-table th,
    .monitoring-table td {
        white-space: nowrap;
        font-size: 0.85rem;
        vertical-align: middle;
    }
    .monitoring-table th.group {
        text-align: center;
        background: rgba(0, 0, 0, 0.03);
    }
    .monitoring-preview-frame {
        width: 100%;
        min-height: 70vh;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        background: #f8f9fa;
    }
    .monitoring-preview-img {
        max-width: 100%;
        max-height: 70vh;
        display: block;
        margin: 0 auto;
    }
    .monitoring-belum-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr));
        gap: 0.35rem 0.75rem;
    }
</style>

<form class="siswa-index-toolbar mb-3" method="GET" action="{{ route('siswa.monitoring') }}" id="monitoringFilterForm">
    <div class="siswa-index-toolbar__filters flex-wrap gap-2">
        <select class="form-select" name="tingkat" aria-label="Filter tingkat" onchange="this.form.rombel_id.value=''; this.form.submit()">
            <option value="">Semua tingkat</option>
            @foreach ($tingkat_options as $option)
                <option value="{{ $option }}" @selected($tingkat === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <select class="form-select" name="rombel_id" aria-label="Filter rombel" onchange="this.form.submit()">
            <option value="">Semua rombel</option>
            @foreach ($rombels as $rombel)
                <option value="{{ $rombel->id }}" @selected((string) $rombel_id === (string) $rombel->id)>{{ $rombel->label() }}</option>
            @endforeach
        </select>
        <select class="form-select" name="status_lengkap" aria-label="Status kelengkapan" onchange="this.form.submit()">
            <option value="">Semua status lengkap</option>
            <option value="sudah_lengkap" @selected($status_lengkap === 'sudah_lengkap')>Sudah lengkap</option>
            <option value="belum_lengkap" @selected($status_lengkap === 'belum_lengkap')>Belum lengkap (ada yang kurang)</option>
            <option value="belum_lengkap_semua" @selected($status_lengkap === 'belum_lengkap_semua')>Belum lengkap semua variabel</option>
        </select>
    </div>
    <div class="siswa-index-toolbar__search">
        <input class="form-control" type="search" name="q" value="{{ $q }}" placeholder="Cari nama, NISN, NIS">
        <input type="hidden" name="per_page" value="{{ $per_page }}">
        <button class="btn btn-outline-secondary" type="submit">Cari</button>
        <a class="btn btn-outline-success" href="{{ route('siswa.monitoring.export', request()->query()) }}">Excel</a>
    </div>
</form>

<div class="madani-card mb-3 p-3">
    <div class="fw-semibold mb-2">Belum lengkap per variabel</div>
    <div class="monitoring-belum-grid">
        @foreach ($variabelLabels as $key => $label)
            <label class="form-check">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="belum[]"
                    value="{{ $key }}"
                    form="monitoringFilterForm"
                    @checked(in_array($key, $belum, true))
                    onchange="document.getElementById('monitoringFilterForm').submit()"
                >
                <span class="form-check-label">{{ $label }}</span>
            </label>
        @endforeach
    </div>
</div>

<div class="madani-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle monitoring-table">
            <thead>
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">Nama</th>
                    <th rowspan="2">NISN</th>
                    <th rowspan="2">Angkatan</th>
                    <th rowspan="2">Rombel</th>
                    <th rowspan="2">Status</th>
                    <th class="group" colspan="3">Akun</th>
                    <th class="group" colspan="4">Data wajib</th>
                    <th class="group" colspan="7">Dokumen</th>
                    <th class="group" colspan="3">Artefak</th>
                    <th rowspan="2" title="Pengajuan pending">Ajuan</th>
                </tr>
                <tr>
                    <th title="Login Ta'lim">Login</th>
                    <th>FCM</th>
                    <th title="Password sudah diganti">Pwd</th>
                    <th>Id</th>
                    <th>Ortu</th>
                    <th>Almt</th>
                    <th>Rekam</th>
                    <th>Foto</th>
                    <th>KK</th>
                    <th>Akta</th>
                    <th>KIP</th>
                    <th>KKS</th>
                    <th>PKH</th>
                    <th>Ijazah</th>
                    <th title="Pernyataan biodata">Bio</th>
                    <th title="Pernyataan peserta didik">PD</th>
                    <th>Kartu</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @php
                        $f = $row['flags'];
                        $p = $row['previews'];
                        $loginTitle = $row['last_login_at']
                            ? 'Terakhir login: '.$row['last_login_at']->timezone(config('app.timezone'))->format('d/m/Y H:i')
                            : 'Belum pernah login';
                    @endphp
                    <tr>
                        <td>{{ $rows->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ $row['show_url'] }}">{{ $row['nama'] }}</a>
                            @if (! $f['nis'])
                                <span class="text-danger" title="NIS kosong">!</span>
                            @endif
                        </td>
                        <td>{{ $row['nisn'] ?: '—' }}</td>
                        <td>{{ $row['angkatan'] ?: '—' }}</td>
                        <td>{{ $row['rombel_label'] }}</td>
                        <td>{{ $row['status_keaktifan'] }}</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['login'], 'title' => $loginTitle])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['fcm']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['password']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['data-siswa']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['orang-tua']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['alamat']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['rekam-didik']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['foto'], 'preview' => $p['foto'] ?? null, 'label' => 'Foto — '.$row['nama']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['kk'], 'preview' => $p['kk'] ?? null, 'label' => 'KK — '.$row['nama']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['akta_lahir'], 'preview' => $p['akta_lahir'] ?? null, 'label' => 'Akta — '.$row['nama']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['kip'], 'preview' => $p['kip'] ?? null, 'label' => 'KIP — '.$row['nama']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['kks'], 'preview' => $p['kks'] ?? null, 'label' => 'KKS — '.$row['nama']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['pkh'], 'preview' => $p['pkh'] ?? null, 'label' => 'PKH — '.$row['nama']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['ijazah_sd'], 'preview' => $p['ijazah_sd'] ?? null, 'label' => 'Ijazah SD — '.$row['nama']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['pernyataan'], 'preview' => $p['pernyataan_biodata'] ?? null, 'label' => 'Pernyataan biodata — '.$row['nama']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['pernyataan'], 'preview' => $p['pernyataan_peserta_didik'] ?? null, 'label' => 'Pernyataan peserta didik — '.$row['nama']])</td>
                        <td>@include('siswa.partials.monitoring-flag', ['ok' => $f['kartu'], 'preview' => $p['kartu'] ?? null, 'label' => 'Kartu e-pelajar — '.$row['nama']])</td>
                        <td>
                            @if ($row['pengajuan_pending'] > 0)
                                <a href="{{ $row['show_url'] }}" class="text-warning fw-semibold" title="Ada pengajuan pending">{{ $row['pengajuan_pending'] }}</a>
                            @else
                                <span class="text-secondary">0</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="24" class="text-center text-secondary py-4">Tidak ada data sesuai filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('siswa.partials.monitoring-pagination', [
        'rows' => $rows,
        'q' => $q,
        'tingkat' => $tingkat,
        'rombelId' => $rombel_id,
        'perPage' => (string) $per_page,
        'statusLengkap' => $status_lengkap,
        'belum' => $belum,
    ])
</div>

<div class="modal fade" id="monitoringPreviewModal" tabindex="-1" aria-labelledby="monitoringPreviewTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="monitoringPreviewTitle">Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body" id="monitoringPreviewBody"></div>
            <div class="modal-footer">
                <a class="btn btn-madani" id="monitoringPreviewDownload" href="#" target="_blank" rel="noopener">Unduh</a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const modalEl = document.getElementById('monitoringPreviewModal');
    if (!modalEl) return;
    const titleEl = document.getElementById('monitoringPreviewTitle');
    const bodyEl = document.getElementById('monitoringPreviewBody');
    const downloadEl = document.getElementById('monitoringPreviewDownload');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    document.querySelectorAll('[data-monitoring-preview]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const label = btn.getAttribute('data-label') || 'Preview';
            const previewUrl = btn.getAttribute('data-preview-url') || '';
            const downloadUrl = btn.getAttribute('data-download-url') || previewUrl;
            const isPdf = btn.getAttribute('data-is-pdf') === '1';
            const external = btn.getAttribute('data-external') === '1';

            if (external) {
                window.open(previewUrl, '_blank', 'noopener');
                return;
            }

            titleEl.textContent = label;
            downloadEl.href = downloadUrl;
            bodyEl.innerHTML = '';

            if (isPdf) {
                const frame = document.createElement('iframe');
                frame.className = 'monitoring-preview-frame';
                frame.src = previewUrl;
                frame.title = label;
                bodyEl.appendChild(frame);
            } else {
                const img = document.createElement('img');
                img.className = 'monitoring-preview-img';
                img.src = previewUrl;
                img.alt = label;
                bodyEl.appendChild(img);
            }

            modal.show();
        });
    });
})();
</script>
@endsection
