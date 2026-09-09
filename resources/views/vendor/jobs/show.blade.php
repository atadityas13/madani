@extends('layouts.app')

@php
    use App\Services\Vendor\VendorFotoService;
    $fotoService = app(VendorFotoService::class);
@endphp

@section('title', $job->nama)
@section('heading', $job->nama)
@section('subheading', 'Kelola foto dan cetak kartu')

@section('content')
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('vendor.jobs.index') }}">
        <i class="bi bi-arrow-left"></i> Job saya
    </a>
    @unless ($job->isAktif())
        <span class="badge text-bg-warning">Job tidak aktif — unggah foto dinonaktifkan</span>
    @endunless
</div>

<div class="madani-card p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-2">
        <div class="stat-label mb-0">Progress foto</div>
        <div class="small text-secondary">{{ number_format($sudahFoto) }} / {{ number_format($total) }} siswa ({{ $progress }}%)</div>
    </div>
    <div class="progress" style="height: 0.65rem;">
        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
</div>

<div class="madani-card p-3 mb-3">
    <form class="row g-2 align-items-end" method="GET" action="{{ route('vendor.jobs.show', $job) }}">
        <div class="col-md-3">
            <label class="form-label small mb-1">Rombel</label>
            <select class="form-select form-select-sm" name="rombel_id" onchange="this.form.submit()">
                <option value="">Semua rombel</option>
                @foreach ($rombels as $rombel)
                    <option value="{{ $rombel->id }}" @selected((string) $rombelId === (string) $rombel->id)>{{ $rombel->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Status foto</label>
            <div class="d-flex flex-wrap gap-1">
                @foreach (['semua' => 'Semua', 'sudah' => 'Sudah foto', 'belum' => 'Belum foto'] as $value => $label)
                    <a
                        class="btn btn-sm {{ $foto === $value ? 'btn-madani' : 'btn-outline-secondary' }}"
                        href="{{ route('vendor.jobs.show', array_filter(['vendorJob' => $job, 'rombel_id' => $rombelId ?: null, 'foto' => $value !== 'semua' ? $value : null, 'q' => $q ?: null])) }}"
                    >{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="col-md-5">
            <label class="form-label small mb-1">Cari</label>
            <div class="input-group input-group-sm">
                <input class="form-control" type="search" name="q" value="{{ $q }}" placeholder="Nama / NISN">
                @if ($foto !== 'semua')
                    <input type="hidden" name="foto" value="{{ $foto }}">
                @endif
                <button class="btn btn-outline-secondary" type="submit">Cari</button>
            </div>
        </div>
    </form>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    @can('uploadFoto', $job)
        <form method="POST" action="{{ route('vendor.jobs.zip', $job) }}" enctype="multipart/form-data" class="d-inline" data-loading-text="Mengimpor ZIP…">
            @csrf
            <label class="btn btn-outline-secondary mb-0">
                <i class="bi bi-file-zip"></i> Unggah ZIP
                <input type="file" name="zip" accept=".zip,application/zip" class="d-none" onchange="if (this.files[0]) this.form.requestSubmit()">
            </label>
        </form>
    @endcan

    @can('printKartu', $job)
        <form method="POST" action="{{ route('vendor.jobs.kartu.bulk', $job) }}" id="vendorBulkForm" data-confirm="Cetak kartu depan+belakang untuk siswa yang dicentang?" data-confirm-title="Cetak terpilih" data-no-loading>
            @csrf
            <button class="btn btn-madani" type="submit" id="vendorBulkBtn" disabled>
                <i class="bi bi-printer"></i> Cetak terpilih
            </button>
        </form>
        <form method="POST" action="{{ route('vendor.jobs.kartu.bulk', $job) }}" data-confirm="Cetak semua siswa yang sudah punya foto di job ini?" data-confirm-title="Cetak semua berfoto" data-no-loading>
            @csrf
            <input type="hidden" name="semua_berfoto" value="1">
            <button class="btn btn-outline-secondary" type="submit" @disabled($sudahFoto < 1)>
                Cetak semua berfoto
            </button>
        </form>
        <button type="button" class="btn btn-sm btn-link" id="vendorSelectAllFoto">Pilih semua berfoto</button>
    @endcan
</div>

<div class="madani-card p-0">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    @can('printKartu', $job)
                        <th style="width: 2.5rem;">
                            <input type="checkbox" class="form-check-input" id="vendorCheckAll" aria-label="Pilih semua">
                        </th>
                    @endcan
                    <th style="width: 3.5rem;">Foto</th>
                    <th>Nama</th>
                    <th>NISN</th>
                    <th>Rombel</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($siswas as $siswa)
                    @php
                        $rombel = $siswa->rombels->first();
                        $punyaFoto = filled($siswa->foto);
                        $thumb = $fotoService->thumbnailUrl($siswa);
                    @endphp
                    <tr>
                        @can('printKartu', $job)
                            <td>
                                @if ($punyaFoto)
                                    <input type="checkbox" class="form-check-input vendor-siswa-check" name="siswa_ids[]" value="{{ $siswa->id }}" form="vendorBulkForm">
                                @else
                                    <input type="checkbox" class="form-check-input" disabled title="Unggah foto dulu">
                                @endif
                            </td>
                        @endcan
                        <td>
                            @if ($thumb)
                                <img src="{{ $thumb }}" alt="" width="40" height="53" class="rounded border" style="object-fit: cover;">
                            @else
                                <div class="bg-light border rounded d-inline-block" style="width:40px;height:53px;"></div>
                            @endif
                        </td>
                        <td>{{ $siswa->nama }}</td>
                        <td>{{ $siswa->nisn ?: '—' }}</td>
                        <td>{{ $rombel?->label() ?? '—' }}</td>
                        <td>
                            @if ($punyaFoto)
                                <span class="badge text-bg-success">Sudah</span>
                            @else
                                <span class="badge text-bg-warning">Belum</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="emis-aksi justify-content-end">
                                @can('uploadFoto', $job)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-vendor-foto-open
                                        data-siswa-id="{{ $siswa->id }}"
                                        data-siswa-nama="{{ $siswa->nama }}"
                                        data-has-foto="{{ $punyaFoto ? '1' : '0' }}"
                                        data-upload-url="{{ route('vendor.jobs.foto.upload', [$job, $siswa]) }}"
                                    >
                                        {{ $punyaFoto ? 'Ganti' : 'Upload' }}
                                    </button>
                                @endcan
                                @if ($punyaFoto)
                                    <a class="emis-aksi-btn" href="{{ route('vendor.jobs.kartu.stream', [$job, $siswa]) }}" target="_blank" rel="noopener" title="Preview kartu">
                                        <i class="bi bi-credit-card-2-front"></i>
                                    </a>
                                @else
                                    <button
                                        type="button"
                                        class="emis-aksi-btn"
                                        style="color:#9ca3af;background:#f3f4f6;cursor:not-allowed;"
                                        title="Upload foto terlebih dahulu"
                                        onclick="window.madaniAlert ? window.madaniAlert.warning('Upload foto terlebih dahulu untuk cetak kartu.', 'Belum bisa dicetak') : alert('Upload foto terlebih dahulu untuk cetak kartu.')"
                                    >
                                        <i class="bi bi-credit-card-2-front"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->can('printKartu', $job) ? 7 : 6 }}" class="text-secondary p-3">Tidak ada siswa dengan filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('uploadFoto', $job)
    <div class="modal fade" id="vendorFotoModal" tabindex="-1" aria-labelledby="vendorFotoModalLabel" aria-hidden="true" data-vendor-foto-modal>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="vendorFotoModalLabel">Upload foto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-secondary mb-3" data-vendor-foto-siswa></p>
                    <div class="vendor-dropzone mb-3" data-vendor-dropzone>
                        <i class="bi bi-cloud-arrow-up fs-3 d-block mb-2"></i>
                        <div>Seret foto ke sini atau klik untuk memilih</div>
                        <div class="small text-secondary mt-1">JPG/PNG · rasio 3:4 · maks. 500 KB</div>
                        <input type="file" accept="image/jpeg,image/png,image/jpg" class="d-none" data-vendor-file-input>
                    </div>
                    <div class="vendor-cropper-wrap d-none" data-vendor-cropper-wrap>
                        <div class="vendor-cropper-container">
                            <img src="" alt="Pratinjau crop" data-vendor-crop-image data-cropper-aspect-ratio="0.75">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-madani" data-vendor-foto-submit disabled>Simpan foto</button>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection
