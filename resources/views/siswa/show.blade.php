@php
    $portal = $portal ?? false;
    $navigasi = $navigasi ?? \App\Support\KelengkapanSiswa::navigasi($siswa, $tab ?? 'data-siswa');
    $inisialSiswa = collect(preg_split('/\s+/', trim($siswa->nama)))
        ->filter()
        ->take(2)
        ->map(fn ($p) => strtoupper(substr($p, 0, 1)))
        ->implode('');
    $updateAction = $portal ? route('siswa.portal.update') : route('siswa.update', $siswa);
    $relasiAction = $portal ? route('siswa.portal.relasi.destroy') : route('siswa.relasi.destroy', $siswa);
    $tabUrl = fn (string $id) => $portal
        ? route('siswa.portal', ['tab' => $id])
        : route('siswa.show', ['siswa' => $siswa, 'tab' => $id]);
@endphp

@extends($portal ? 'layouts.siswa' : 'layouts.app')

@section('title', $portal ? 'Data saya' : $siswa->nama)
@section('heading', $portal ? 'Data saya' : 'Data siswa')
@section('subheading', 'MTsN 11 Majalengka')

@section('content')
<div class="emis-student-head mb-3">
    <div class="emis-photo">{{ $inisialSiswa ?: 'SW' }}</div>
    <div>
        <div class="emis-student-name">{{ $siswa->nama }}</div>
        <div class="emis-student-meta">
            NISN {{ $siswa->nisn ?: 'belum ada' }}
            · {{ str_replace('_', ' ', $siswa->status_keaktifan) }}
        </div>
    </div>
    @if (! $portal)
        <div class="ms-auto d-flex gap-2 flex-wrap justify-content-end">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('siswa.portofolio', $siswa) }}">Portofolio</a>
            @if (auth()->user()?->mengampu($siswa) && $siswa->tanggal_lahir)
                <form method="POST" action="{{ route('siswa.reset-password', $siswa) }}" onsubmit="return confirm('Reset password ke tanggal lahir (ddmmyyyy)? Siswa wajib mengubahnya saat masuk.')">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Reset password</button>
                </form>
            @endif
        </div>
    @endif
</div>

<div class="madani-card biodata-path-wrap px-2 mb-3">
    @include('siswa.partials.tab-path')
</div>

@if ($tab === 'data-siswa')
    @if (! $portal && $siswa->pengajuanPerubahans->where('status', 'pending')->isNotEmpty())
        <div class="madani-card p-4 mb-3">
            <div class="stat-label mb-3">Pengajuan perubahan identitas</div>
            @foreach ($siswa->pengajuanPerubahans->where('status', 'pending') as $pengajuan)
                <div class="border rounded p-3 mb-2">
                    <div class="fw-semibold">{{ \App\Models\PengajuanPerubahanSiswa::FIELDS[$pengajuan->field] ?? $pengajuan->field }}</div>
                    <div class="small text-secondary">Dari: {{ $pengajuan->nilai_lama ?: '—' }} → {{ $pengajuan->nilai_baru }}</div>
                    <div class="small mb-2">Alasan: {{ $pengajuan->alasan }}</div>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('siswa.pengajuan.proses', [$siswa, $pengajuan]) }}">
                            @csrf
                            <input type="hidden" name="aksi" value="terima">
                            <button class="btn btn-sm btn-madani" type="submit">Terima</button>
                        </form>
                        <form method="POST" action="{{ route('siswa.pengajuan.proses', [$siswa, $pengajuan]) }}">
                            @csrf
                            <input type="hidden" name="aksi" value="tolak">
                            <button class="btn btn-sm btn-outline-secondary" type="submit">Tolak</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    <div class="madani-card p-4">
        <form method="POST" action="{{ $updateAction }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="bagian" value="data-siswa">
            @include('siswa.partials.form-data-siswa', ['siswa' => $siswa, 'periodik' => $periodik, 'emis' => $emis])
            @include('siswa.partials.tab-actions')
        </form>
    </div>
    @if ($portal)
        <div class="modal fade" id="modalAjukanPerubahan" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('siswa.portal.pengajuan.store') }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Ajukan perubahan <span data-ajukan-judul></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="field" data-ajukan-field-input>
                        <div class="mb-3">
                            <label class="form-label">Data saat ini</label>
                            <input class="form-control" data-ajukan-lama readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Data baru</label>
                            <input class="form-control" name="nilai_baru" data-ajukan-baru required>
                            <select class="form-select d-none" data-ajukan-jk>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Alasan perubahan</label>
                            <textarea class="form-control" name="alasan" rows="3" minlength="10" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-madani" type="submit">Kirim pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endif

@if ($tab === 'orang-tua')
    <form method="POST" action="{{ $updateAction }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="bagian" value="orang-tua">
        <div class="row g-3">
            @foreach (['ayah', 'ibu', 'wali'] as $peran)
                <div class="col-12 col-md-4">
                    @include('siswa.partials.form-ortu-blok', ['peran' => $peran])
                </div>
            @endforeach
        </div>
        <div class="madani-card p-4 mt-3">
            <div class="stat-label mb-3">Penghasilan Gabungan Orang tua & Data Bantuan</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nominal penghasilan gabungan orang tua <span class="text-danger">*</span></label>
                    <x-emis-select name="penghasilan_gabungan" :options="$emis['penghasilan_gabungan']" :value="old('penghasilan_gabungan', $periodik?->penghasilan_gabungan)" required />
                </div>
                <div class="col-md-4" data-bantuan-kartu="kks">
                    <label class="form-label">Nomor KKS <span class="text-danger">*</span></label>
                    <input class="form-control" name="no_kks" value="{{ old('no_kks', $periodik?->no_kks) }}" data-nomor>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="tidak_punya_kks" value="1" id="tidak_punya_kks" @checked(old('tidak_punya_kks', $periodik?->tidak_punya_kks)) data-tidak-punya>
                        <label class="form-check-label small" for="tidak_punya_kks">Tidak memiliki KKS</label>
                    </div>
                    <div data-bantuan-upload @if (blank(old('no_kks', $periodik?->no_kks)) || old('tidak_punya_kks', $periodik?->tidak_punya_kks)) hidden @endif>
                        <div class="mt-2">
                            <x-dokumen-box
                                judul="Kartu Keluarga Sejahtera"
                                name="file_kks"
                                jenis="kks"
                                :dokumen="$siswa->dokumenJenis('kks')"
                                :siswa="$portal ? null : $siswa"
                                hint="Wajib jika nomor KKS diisi. Maks. 1MB · pdf / jpg / png"
                            />
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-bantuan-kartu="pkh">
                    <label class="form-label">Nomor PKH <span class="text-danger">*</span></label>
                    <input class="form-control" name="no_pkh" value="{{ old('no_pkh', $periodik?->no_pkh) }}" data-nomor>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="tidak_punya_pkh" value="1" id="tidak_punya_pkh" @checked(old('tidak_punya_pkh', $periodik?->tidak_punya_pkh)) data-tidak-punya>
                        <label class="form-check-label small" for="tidak_punya_pkh">Tidak memiliki PKH</label>
                    </div>
                    <div data-bantuan-upload @if (blank(old('no_pkh', $periodik?->no_pkh)) || old('tidak_punya_pkh', $periodik?->tidak_punya_pkh)) hidden @endif>
                        <div class="mt-2">
                            <x-dokumen-box
                                judul="Kartu Program Keluarga Harapan"
                                name="file_pkh"
                                jenis="pkh"
                                :dokumen="$siswa->dokumenJenis('pkh')"
                                :siswa="$portal ? null : $siswa"
                                hint="Wajib jika nomor PKH diisi. Maks. 1MB · pdf / jpg / png"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('siswa.partials.tab-actions')
    </form>
@endif

@if ($tab === 'alamat')
    <form method="POST" action="{{ $updateAction }}" data-alamat-form>
        @csrf
        @method('PUT')
        <input type="hidden" name="bagian" value="alamat">
        <div class="row g-3">
            @foreach (['ayah', 'ibu', 'wali'] as $peran)
                <div class="col-12">
                    @include('siswa.partials.form-alamat-ortu', ['peran' => $peran])
                </div>
            @endforeach
        </div>
        <div class="madani-card p-4 mt-3" data-alamat-siswa>
            <div class="stat-label mb-3">Alamat Siswa</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Status tempat tinggal</label>
                    <x-emis-select name="tempat_tinggal" :options="$emis['status_tempat_tinggal_siswa']" :value="old('tempat_tinggal', $periodik?->tempat_tinggal)" data-tempat-tinggal />
                    <div class="form-text" data-alamat-ortu-kosong hidden>Lengkapi alamat orang tua terlebih dahulu.</div>
                    <div class="mt-3" data-siswa-alamat-isi @if (blank(old('tempat_tinggal', $periodik?->tempat_tinggal))) hidden @endif>
                        @include('siswa.partials.form-wilayah', [
                            'namePrefix' => '',
                            'oldPrefix' => '',
                            'record' => $periodik,
                            'root' => 'siswa',
                            'wide' => false,
                        ])
                    </div>
                </div>
                <div class="col-md-6" data-siswa-alamat-isi @if (blank(old('tempat_tinggal', $periodik?->tempat_tinggal))) hidden @endif>
                    <label class="form-label">Titik koordinat</label>
                    <input class="form-control bg-light" name="koordinat" value="{{ old('koordinat', $periodik?->koordinat) }}" placeholder="-7.043314, 108.353711" data-koordinat readonly>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-lokasi-saat-ini>
                            <i class="bi bi-geo-alt"></i> Ambil lokasi saat ini
                        </button>
                        <div class="form-text mb-0" data-lokasi-status>Geser penanda di peta untuk menyesuaikan titik lokasi rumah.</div>
                    </div>
                    <div class="madani-map mt-2" data-siswa-map></div>
                </div>
                <div class="col-md-4" data-siswa-alamat-isi @if (blank(old('tempat_tinggal', $periodik?->tempat_tinggal))) hidden @endif>
                    <label class="form-label">Jarak tempat tinggal – madrasah</label>
                    <x-emis-select name="jarak" :options="$emis['jarak']" :value="old('jarak', $periodik?->jarak)" />
                </div>
                <div class="col-md-4" data-siswa-alamat-isi @if (blank(old('tempat_tinggal', $periodik?->tempat_tinggal))) hidden @endif>
                    <label class="form-label">Waktu tempuh</label>
                    <x-emis-select name="waktu_tempuh" :options="$emis['waktu_tempuh']" :value="old('waktu_tempuh', $periodik?->waktu_tempuh)" />
                </div>
                <div class="col-md-4" data-siswa-alamat-isi @if (blank(old('tempat_tinggal', $periodik?->tempat_tinggal))) hidden @endif>
                    <label class="form-label">Transportasi ke sekolah</label>
                    <x-emis-select name="transportasi" :options="$emis['transportasi']" :value="old('transportasi', $periodik?->transportasi)" />
                </div>
            </div>
        </div>
        <script type="application/json" id="madani-alamat-ortu">@json($alamatOrtu ?? [])</script>
        <script type="application/json" id="madani-alamat-asrama">@json($alamatAsrama ?? [])</script>
        @include('siswa.partials.tab-actions')
    </form>
@endif

@if ($tab === 'aktivitas')
    @include('siswa.partials.riwayat-akademik')
    @include('siswa.partials.tab-actions', ['showSave' => false])
@endif

@if ($tab === 'beasiswa')
    @include('siswa.partials.beasiswa')
    @include('siswa.partials.tab-actions', ['showSave' => false])
@endif

@if ($tab === 'prestasi')
    @include('siswa.partials.prestasi')
    @include('siswa.partials.tab-actions', ['showSave' => false])
@endif

@if ($tab === 'rekam-didik')
    <form method="POST" action="{{ $updateAction }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="bagian" value="rekam-didik">
        @include('siswa.partials.form-rekam-didik')
        @include('siswa.partials.tab-actions')
    </form>
@endif
@endsection
