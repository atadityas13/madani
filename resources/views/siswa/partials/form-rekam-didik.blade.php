@php
    $rd = $siswa->rekamDidik;
    $ayah = $siswa->ayah;
    $ibu = $siswa->ibu;
    $ijazahSd = $siswa->dokumenJenis('ijazah_sd');

    $namaKk = old('nama_kk', $rd?->nama_kk ?: $siswa->nama);
    $nikKk = old('nik_kk', $rd?->nik_kk ?: $siswa->nik);
    $tempatLahirKk = old('tempat_lahir_kk', $rd?->tempat_lahir_kk ?: $siswa->tempat_lahir);
    $tanggalLahirKk = old('tanggal_lahir_kk', ($rd?->tanggal_lahir_kk ?? $siswa->tanggal_lahir)?->format('Y-m-d'));
    $jkKk = old('jenis_kelamin_kk', $rd?->jenis_kelamin_kk ?: $siswa->jenis_kelamin);
    $namaIbuKk = old('nama_ibu_kk', $rd?->nama_ibu_kk ?: $ibu?->nama);
    $namaAyahKk = old('nama_ayah_kk', $rd?->nama_ayah_kk ?: $ayah?->nama);

    $namaIjazah = old('nama_ijazah', $rd?->nama_ijazah);
    $tempatLahirIjazah = old('tempat_lahir_ijazah', $rd?->tempat_lahir_ijazah);
    $tanggalLahirIjazah = old('tanggal_lahir_ijazah', $rd?->tanggal_lahir_ijazah?->format('Y-m-d'));
    $jkIjazah = old('jenis_kelamin_ijazah', $rd?->jenis_kelamin_ijazah);
    $namaAyahIjazah = old('nama_ayah_ijazah', $rd?->nama_ayah_ijazah);

    $namaSd = old('nama_sd', $rd?->nama_sd);
    $tahunAjaranKelulusan = old('tahun_ajaran_kelulusan', $rd?->tahun_ajaran_kelulusan);
    $nipKepala = old('nip_kepala_sekolah', $rd?->nip_kepala_sekolah);
    $namaKepala = old('nama_kepala_sekolah', $rd?->nama_kepala_sekolah);
    $nomorSeri = old('nomor_seri_ijazah', $rd?->nomor_seri_ijazah);
    $tanggalTerbit = old('tanggal_terbit_ijazah', $rd?->tanggal_terbit_ijazah?->format('Y-m-d'));
    $valStatusVerval = old('status_verval', $rd?->status_verval ?? 'belum');

    $beda = [];
    $banding = [
        'Nama' => [$namaKk, $namaIjazah],
        'Tempat lahir' => [$tempatLahirKk, $tempatLahirIjazah],
        'Tanggal lahir' => [$tanggalLahirKk, $tanggalLahirIjazah],
        'Jenis kelamin' => [$jkKk, $jkIjazah],
        'Nama ayah' => [$namaAyahKk, $namaAyahIjazah],
    ];
    foreach ($banding as $label => [$kiri, $kanan]) {
        if ($kiri && $kanan && mb_strtoupper(trim((string) $kiri)) !== mb_strtoupper(trim((string) $kanan))) {
            $beda[] = $label;
        }
    }
@endphp

<p class="text-secondary small mb-3">
    Verval rekam didik jenjang sebelum MTsN 11 Majalengka. Bandingkan identitas di Kartu Keluarga dengan ijazah SD/MI, lalu lengkapi data sekolah asal.
</p>

@if (count($beda))
    <div class="alert alert-warning">
        Data KK dan ijazah berbeda pada: {{ implode(', ', $beda) }}.
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-6">
        <div class="madani-card p-4 h-100">
            <div class="stat-label mb-3">Data dari Kartu Keluarga</div>
            <div class="mb-3">
                <label class="form-label">NIK</label>
                <input class="form-control @error('nik_kk') is-invalid @enderror" name="nik_kk" value="{{ $nikKk }}" maxlength="16" inputmode="numeric">
                @error('nik_kk') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Nama</label>
                <input class="form-control" name="nama_kk" value="{{ $namaKk }}">
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Tempat lahir</label>
                    <input class="form-control" name="tempat_lahir_kk" value="{{ $tempatLahirKk }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal lahir</label>
                    <input class="form-control" type="date" name="tanggal_lahir_kk" value="{{ $tanggalLahirKk }}">
                </div>
            </div>
            <div class="mb-3 mt-3">
                <label class="form-label">Jenis kelamin</label>
                <select class="form-select" name="jenis_kelamin_kk">
                    <option value="">Pilih</option>
                    <option value="L" @selected($jkKk === 'L')>Laki-laki</option>
                    <option value="P" @selected($jkKk === 'P')>Perempuan</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Nama ibu</label>
                <input class="form-control" name="nama_ibu_kk" value="{{ $namaIbuKk }}">
            </div>
            <div class="mb-0">
                <label class="form-label">Nama ayah</label>
                <input class="form-control" name="nama_ayah_kk" value="{{ $namaAyahKk }}">
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="madani-card p-4 h-100">
            <div class="stat-label mb-3">Data dari ijazah</div>
            <div class="mb-3">
                <label class="form-label">Nama</label>
                <input class="form-control" name="nama_ijazah" value="{{ $namaIjazah }}">
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Tempat lahir</label>
                    <input class="form-control" name="tempat_lahir_ijazah" value="{{ $tempatLahirIjazah }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal lahir</label>
                    <input class="form-control" type="date" name="tanggal_lahir_ijazah" value="{{ $tanggalLahirIjazah }}">
                </div>
            </div>
            <div class="mb-3 mt-3">
                <label class="form-label">Jenis kelamin</label>
                <select class="form-select" name="jenis_kelamin_ijazah">
                    <option value="">Pilih</option>
                    <option value="L" @selected($jkIjazah === 'L')>Laki-laki</option>
                    <option value="P" @selected($jkIjazah === 'P')>Perempuan</option>
                </select>
            </div>
            <div class="mb-0">
                <label class="form-label">Nama ayah</label>
                <input class="form-control" name="nama_ayah_ijazah" value="{{ $namaAyahIjazah }}">
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="madani-card p-4">
            <div class="stat-label mb-3">Jenjang sebelumnya (SD/MI)</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama sekolah</label>
                    <input class="form-control" name="nama_sd" value="{{ $namaSd }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tahun ajaran kelulusan</label>
                    <input class="form-control" name="tahun_ajaran_kelulusan" value="{{ $tahunAjaranKelulusan }}" placeholder="2023/2024">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status verval</label>
                    <x-emis-select name="status_verval" :options="$emis['status_verval']" :value="$valStatusVerval" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">NIP kepala sekolah</label>
                    <input class="form-control" name="nip_kepala_sekolah" value="{{ $nipKepala }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Nama kepala sekolah</label>
                    <input class="form-control" name="nama_kepala_sekolah" value="{{ $namaKepala }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nomor seri ijazah</label>
                    <input class="form-control" name="nomor_seri_ijazah" value="{{ $nomorSeri }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal terbit ijazah</label>
                    <input class="form-control" type="date" name="tanggal_terbit_ijazah" value="{{ $tanggalTerbit }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Unggah ijazah</label>
                    <input class="form-control @error('file_ijazah') is-invalid @enderror" type="file" name="file_ijazah" accept=".pdf,.jpg,.jpeg,.png">
                    <div class="form-text">Maks. 2MB, pdf / jpg / png</div>
                    @error('file_ijazah') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    @if ($ijazahSd)
                        <div class="form-text">Berkas saat ini: {{ $ijazahSd->nama_asli }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
