@extends('layouts.app')

@section('title', 'Database')
@section('heading', 'Database')
@section('subheading', 'Manajemen')

@section('content')
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Impor gagal</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($imporSuksesJumlah !== null)
    <div
        class="modal fade"
        id="imporSiswaSuksesModal"
        tabindex="-1"
        aria-labelledby="imporSiswaSuksesModalLabel"
        aria-hidden="true"
        data-modal-open
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="imporSiswaSuksesModalLabel">Impor berhasil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="mb-0 fs-5">Berhasil Import {{ number_format((int) $imporSuksesJumlah) }} siswa</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-madani" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalEl = document.getElementById('imporSiswaSuksesModal');
            if (modalEl && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });
    </script>
@endif

@if (! empty($imporJurnalHasil))
    <div
        class="modal fade"
        id="imporJurnalSuksesModal"
        tabindex="-1"
        aria-labelledby="imporJurnalSuksesModalLabel"
        aria-hidden="true"
        data-modal-open
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="imporJurnalSuksesModalLabel">Impor jurnal selesai</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="mb-2">
                        Baru: {{ number_format((int) $imporJurnalHasil['imported']) }} ·
                        Diperbarui: {{ number_format((int) $imporJurnalHasil['updated']) }} ·
                        Dilewati: {{ number_format((int) $imporJurnalHasil['skipped']) }}
                    </p>
                    <p class="mb-0 small text-secondary">
                        Sumber: {{ $imporJurnalHasil['source_rows'] }} baris dari tabel {{ $imporJurnalHasil['table'] }}.
                        Impor tidak menggabung jam; untuk gabung opsional jalankan
                        <code>php artisan jurnal:consolidate-entries</code>.
                    </p>
                    @if (! empty($imporJurnalHasil['orphans']))
                        <div class="mt-3 small text-secondary">
                            <div class="fw-semibold mb-1">Dilewati (ringkas):</div>
                            <ul class="mb-0 ps-3">
                                @foreach (array_slice($imporJurnalHasil['orphans'], 0, 8) as $orphan)
                                    <li>{{ $orphan }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-madani" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalEl = document.getElementById('imporJurnalSuksesModal');
            if (modalEl && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });
    </script>
@endif

<div class="row g-3">
    @foreach ($kartu as $item)
        <div class="col-md-6 col-xl-4">
            <div class="madani-card h-100 p-3 d-flex flex-column">
                <div class="stat-label mb-1">{{ $item['label'] }}</div>
                <div class="small text-secondary mb-3">{{ $item['ringkasan'] }}</div>
                <div class="mt-auto d-flex flex-column gap-2">
                    @if ($item['excel'] && ($item['excel_ready'] ?? false))
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('manajemen.database.siswa.template') }}">Unduh template</a>
                            <button class="btn btn-sm btn-outline-secondary" type="button" disabled title="Segera">Ekspor Excel</button>
                        </div>
                        <form
                            method="POST"
                            action="{{ route('manajemen.database.siswa.impor') }}"
                            enctype="multipart/form-data"
                            class="d-flex flex-wrap gap-2 align-items-center"
                            data-loading-text="Mengimpor…"
                        >
                            @csrf
                            <input class="form-control form-control-sm" type="file" name="file" accept=".xlsx,.xls" required>
                            <button class="btn btn-sm btn-outline-primary" type="submit">Impor Excel</button>
                        </form>
                    @elseif ($item['excel'])
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-sm btn-outline-secondary" type="button" disabled title="Segera">Impor Excel</button>
                            <button class="btn btn-sm btn-outline-secondary" type="button" disabled title="Segera">Ekspor Excel</button>
                        </div>
                    @endif
                    @if ($item['sql_import'] ?? false)
                        <div class="small text-secondary">
                            Unggah dump SQL Simpatisans lengkap. Sistem hanya membaca data jurnal (plus guru/kelas/mapel terkait).
                        </div>
                        <form
                            method="POST"
                            action="{{ route('manajemen.database.jurnal.impor') }}"
                            enctype="multipart/form-data"
                            class="d-flex flex-wrap gap-2 align-items-center"
                            data-loading-text="Mengimpor jurnal…"
                        >
                            @csrf
                            <input class="form-control form-control-sm" type="file" name="file" accept=".sql,.txt" required>
                            <button class="btn btn-sm btn-outline-primary" type="submit">Impor SQL</button>
                        </form>
                    @endif
                    <form
                        method="POST"
                        action="{{ route('manajemen.database.kosongkan', $item['id']) }}"
                        data-confirm="{{ $item['confirm'] }}"
                        data-confirm-title="Kosongkan {{ $item['label'] }}"
                        data-confirm-ok="Kosongkan"
                        data-loading-text="Mengosongkan…"
                    >
                        @csrf
                        <button class="btn btn-sm btn-outline-danger" type="submit">Kosongkan</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if (! empty($imporDuplikat))
    <div
        class="modal fade"
        id="imporSiswaDuplikatModal"
        tabindex="-1"
        aria-labelledby="imporSiswaDuplikatModalLabel"
        aria-hidden="true"
        data-modal-open
    >
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imporSiswaDuplikatModalLabel">Impor gagal — data ganda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">{{ $imporDuplikat['pesan'] }}</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-2" id="imporDuplikatTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>NISN</th>
                                    <th>NIK</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($imporDuplikat['conflicts'] as $index => $item)
                                    <tr class="impor-duplikat-row" data-page-index="{{ (int) floor($index / 10) }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item['nama'] }}</td>
                                        <td>{{ $item['nisn'] }}</td>
                                        <td>{{ $item['nik'] }}</td>
                                        <td>{{ implode(', ', $item['bentrok']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <div class="small text-secondary" id="imporDuplikatPageInfo"></div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="imporDuplikatPrev" disabled>Sebelumnya</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="imporDuplikatNext">Berikutnya</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-wrap gap-2">
                    <a
                        class="btn btn-outline-secondary"
                        href="{{ route('manajemen.database.siswa.ekspor-duplikat', ['token' => $imporDuplikat['token']]) }}"
                    >Ekspor data gagal</a>
                    <form method="POST" action="{{ route('manajemen.database.siswa.impor') }}" data-loading-text="Mengimpor…">
                        @csrf
                        <input type="hidden" name="token" value="{{ $imporDuplikat['token'] }}">
                        <input type="hidden" name="skip_duplikat" value="1">
                        <button class="btn btn-madani" type="submit">Skip data yang ganda</button>
                    </form>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalEl = document.getElementById('imporSiswaDuplikatModal');
            if (modalEl && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            const rows = Array.from(document.querySelectorAll('.impor-duplikat-row'));
            const pageSize = 10;
            const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
            let page = 0;

            const prevBtn = document.getElementById('imporDuplikatPrev');
            const nextBtn = document.getElementById('imporDuplikatNext');
            const info = document.getElementById('imporDuplikatPageInfo');

            const render = () => {
                rows.forEach((row) => {
                    row.classList.toggle('d-none', Number(row.dataset.pageIndex) !== page);
                });
                if (info) {
                    info.textContent = `Halaman ${page + 1} dari ${totalPages} · ${rows.length} data`;
                }
                if (prevBtn) prevBtn.disabled = page <= 0;
                if (nextBtn) nextBtn.disabled = page >= totalPages - 1;
            };

            prevBtn?.addEventListener('click', () => {
                page = Math.max(0, page - 1);
                render();
            });
            nextBtn?.addEventListener('click', () => {
                page = Math.min(totalPages - 1, page + 1);
                render();
            });

            render();
        });
    </script>
@endif
@endsection
