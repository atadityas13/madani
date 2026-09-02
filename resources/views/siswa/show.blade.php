@extends('layouts.app')

@section('title', $siswa->nama)
@section('heading', 'Data siswa')
@section('subheading', 'MTsN 11 Majalengka')

@php
    $tabs = [
        'data-siswa' => 'Data siswa',
        'orang-tua' => 'Data orang tua',
        'alamat' => 'Data alamat',
        'rekam-didik' => 'Rekam didik',
        'aktivitas' => 'Riwayat akademik',
        'prestasi' => 'Prestasi',
        'beasiswa' => 'Bantuan pendidikan',
    ];
    $inisialSiswa = collect(preg_split('/\s+/', trim($siswa->nama)))
        ->filter()
        ->take(2)
        ->map(fn ($p) => strtoupper(substr($p, 0, 1)))
        ->implode('');
@endphp

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
</div>

<div class="madani-card px-2 mb-3">
    <ul class="nav nav-pills flex-nowrap overflow-auto mb-0">
        @foreach ($tabs as $id => $label)
            <li class="nav-item">
                <a class="nav-link {{ $tab === $id ? 'active' : '' }}" href="{{ route('siswa.show', ['siswa' => $siswa, 'tab' => $id]) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>
</div>

@if ($tab === 'data-siswa')
    <div class="madani-card p-4">
        <form method="POST" action="{{ route('siswa.update', $siswa) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="bagian" value="data-siswa">
            @include('siswa.partials.form-data-siswa', ['siswa' => $siswa, 'periodik' => $periodik, 'emis' => $emis])
            <div class="emis-actions">
                <a class="btn btn-outline-secondary" href="{{ route('siswa.index') }}">Kembali</a>
                <button class="btn btn-madani" type="submit">Simpan</button>
            </div>
        </form>
    </div>
@endif

@if ($tab === 'orang-tua')
    <form method="POST" action="{{ route('siswa.update', $siswa) }}" enctype="multipart/form-data">
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
                    <label class="form-label">Nominal penghasilan gabungan orang tua</label>
                    <x-emis-select name="penghasilan_gabungan" :options="$emis['penghasilan_gabungan']" :value="old('penghasilan_gabungan', $periodik?->penghasilan_gabungan)" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nomor KKS</label>
                    <input class="form-control" name="no_kks" value="{{ old('no_kks', $periodik?->no_kks) }}">
                    <div class="form-text">Unggah KKS maks. 2MB pdf jpg png</div>
                    <input class="form-control mt-1" type="file" name="file_kks" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nomor PKH</label>
                    <input class="form-control" name="no_pkh" value="{{ old('no_pkh', $periodik?->no_pkh) }}">
                    <div class="form-text">Unggah PKH maks. 2MB pdf jpg png</div>
                    <input class="form-control mt-1" type="file" name="file_pkh" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>
        </div>
        <div class="emis-actions">
            <a class="btn btn-outline-secondary" href="{{ route('siswa.index') }}">Kembali</a>
            <button class="btn btn-madani" type="submit">Simpan</button>
        </div>
    </form>
@endif

@if ($tab === 'alamat')
    <form method="POST" action="{{ route('siswa.update', $siswa) }}" data-alamat-form>
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
                    <div class="mt-3">
                        @include('siswa.partials.form-wilayah', [
                            'namePrefix' => '',
                            'oldPrefix' => '',
                            'record' => $periodik,
                            'root' => 'siswa',
                            'wide' => false,
                        ])
                    </div>
                </div>
                <div class="col-md-6">
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
                <div class="col-md-4">
                    <label class="form-label">Jarak tempat tinggal – madrasah</label>
                    <x-emis-select name="jarak" :options="$emis['jarak']" :value="old('jarak', $periodik?->jarak)" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Waktu tempuh</label>
                    <x-emis-select name="waktu_tempuh" :options="$emis['waktu_tempuh']" :value="old('waktu_tempuh', $periodik?->waktu_tempuh)" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Transportasi ke sekolah</label>
                    <x-emis-select name="transportasi" :options="$emis['transportasi']" :value="old('transportasi', $periodik?->transportasi)" />
                </div>
            </div>
        </div>
        <script type="application/json" id="madani-alamat-ortu">@json($alamatOrtu ?? [])</script>
        <script type="application/json" id="madani-alamat-asrama">@json($alamatAsrama ?? [])</script>
        <div class="emis-actions">
            <a class="btn btn-outline-secondary" href="{{ route('siswa.index') }}">Kembali</a>
            <button class="btn btn-madani" type="submit">Simpan</button>
        </div>
    </form>
@endif

@if ($tab === 'aktivitas')
    <div class="madani-card p-4">
        <form method="POST" action="{{ route('siswa.update', $siswa) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="bagian" value="aktivitas">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tanggal masuk</label>
                    <input class="form-control" type="date" name="tanggal_masuk" value="{{ old('tanggal_masuk', optional($periodik?->tanggal_masuk)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Alasan masuk</label>
                    <x-emis-select name="alasan_masuk" :options="$emis['alasan_masuk']" :value="old('alasan_masuk', $periodik?->alasan_masuk)" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Rombel</label>
                    <select class="form-select" name="rombel_id">
                        <option value="">Aktif tanpa rombel</option>
                        @foreach ($rombels as $rombel)
                            <option value="{{ $rombel->id }}" @selected((int) old('rombel_id', $siswa->rombelAktif()?->id) === $rombel->id)>
                                {{ $rombel->nama }} · tingkat {{ $rombel->tingkat }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">NPSN sekolah asal</label>
                    <input class="form-control" name="npsn_asal" value="{{ old('npsn_asal', $periodik?->npsn_asal) }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Nama sekolah asal</label>
                    <input class="form-control" name="nama_sekolah_asal" value="{{ old('nama_sekolah_asal', $periodik?->nama_sekolah_asal) }}">
                </div>
            </div>
            <div class="emis-actions">
                <a class="btn btn-outline-secondary" href="{{ route('siswa.index') }}">Kembali</a>
                <button class="btn btn-madani" type="submit">Simpan</button>
            </div>
        </form>
    </div>
@endif

@if ($tab === 'beasiswa')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="madani-card p-4">
                <div class="stat-label mb-3">Tambah beasiswa / bantuan</div>
                <form method="POST" action="{{ route('siswa.update', $siswa) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="bagian" value="beasiswa">
                    <div class="mb-3">
                        <label class="form-label">Tahun</label>
                        <input class="form-control" type="number" name="tahun" value="{{ old('tahun', date('Y')) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis bantuan</label>
                        <x-emis-select name="kategori" :options="$emis['jenis_beasiswa']" :value="old('kategori')" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input class="form-control" name="nama" value="{{ old('nama') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Instansi</label>
                        <input class="form-control" name="instansi" value="{{ old('instansi') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jangka (bulan)</label>
                        <input class="form-control" type="number" name="jangka_bulan" value="{{ old('jangka_bulan') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nominal (Rp)</label>
                        <input class="form-control" type="number" name="nominal" value="{{ old('nominal') }}">
                    </div>
                    <button class="btn btn-madani" type="submit">Tambah</button>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="madani-card p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Tahun</th>
                            <th>Jenis</th>
                            <th>Nama</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siswa->beasiswas as $item)
                            <tr>
                                <td>{{ $item->tahun }}</td>
                                <td>{{ $item->kategori }}</td>
                                <td>{{ $item->nama }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('siswa.relasi.destroy', $siswa) }}" onsubmit="return confirm('Hapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="jenis" value="beasiswa">
                                        <input type="hidden" name="id" value="{{ $item->id }}">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-secondary p-3">Belum ada beasiswa.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@if ($tab === 'prestasi')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="madani-card p-4">
                <div class="stat-label mb-3">Tambah prestasi</div>
                <form method="POST" action="{{ route('siswa.update', $siswa) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="bagian" value="prestasi">
                    <div class="mb-3">
                        <label class="form-label">Nama prestasi / penghargaan</label>
                        <input class="form-control" name="nama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <x-emis-select name="jenis" :options="$emis['jenis_prestasi']" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tingkat</label>
                        <x-emis-select name="tingkat" :options="$emis['tingkat_prestasi']" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tahun</label>
                        <input class="form-control" type="number" name="tahun" value="{{ date('Y') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Penyelenggara</label>
                        <input class="form-control" name="penyelenggara">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sertifikat</label>
                        <input class="form-control" type="file" name="sertifikat" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <button class="btn btn-madani" type="submit">Tambah</button>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="madani-card p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Jenis</th>
                            <th>Tingkat</th>
                            <th>Tahun</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siswa->prestasis as $item)
                            <tr>
                                <td>{{ $item->nama }}</td>
                                <td>{{ $item->jenis ?: '—' }}</td>
                                <td>{{ $item->tingkat ?: '—' }}</td>
                                <td>{{ $item->tahun ?: '—' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('siswa.relasi.destroy', $siswa) }}" onsubmit="return confirm('Hapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="jenis" value="prestasi">
                                        <input type="hidden" name="id" value="{{ $item->id }}">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-secondary p-3">Belum ada prestasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@if ($tab === 'rekam-didik')
    <form method="POST" action="{{ route('siswa.update', $siswa) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="bagian" value="rekam-didik">
        @include('siswa.partials.form-rekam-didik')
        <div class="emis-actions">
            <a class="btn btn-outline-secondary" href="{{ route('siswa.index') }}">Kembali</a>
            <button class="btn btn-madani" type="submit">Simpan</button>
        </div>
    </form>
@endif
@endsection
