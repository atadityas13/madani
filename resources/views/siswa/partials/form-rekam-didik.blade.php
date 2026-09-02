@php
    $rd = $siswa->rekamDidik;
    $periodik = $periodik ?? $siswa->periodikAktif();
    $ayah = $siswa->ayah;
    $ijazahSd = $siswa->dokumenJenis('ijazah_sd');

    $namaSekolah = old('nama_sd', $rd?->nama_sd ?: $periodik?->nama_sekolah_asal);
    $npsn = old('npsn', $rd?->npsn ?: $periodik?->npsn_asal);
    $tahunAjaranLulusan = old('tahun_ajaran_kelulusan', $rd?->tahun_ajaran_kelulusan);
    $nipKepala = old('nip_kepala_sekolah', $rd?->nip_kepala_sekolah);
    $namaKepala = old('nama_kepala_sekolah', $rd?->nama_kepala_sekolah);
    $nomorSeri = old('nomor_seri_ijazah', $rd?->nomor_seri_ijazah);
    $tanggalTerbit = old('tanggal_terbit_ijazah', $rd?->tanggal_terbit_ijazah?->format('Y-m-d'));
    $ijazahSesuai = old('ijazah_sesuai', $rd?->ijazah_sesuai);

    $jkLabel = $siswa->jenis_kelamin === 'P' ? 'Perempuan' : ($siswa->jenis_kelamin === 'L' ? 'Laki-laki' : '—');
    $namaAyah = $ayah?->nama ?: $rd?->nama_ayah_ijazah ?: $rd?->nama_ayah_kk;
@endphp

<div class="madani-card p-4 mb-3">
    <div class="stat-label mb-3">Data jenjang sebelumnya</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nama sekolah</label>
            <input class="form-control" name="nama_sd" value="{{ $namaSekolah }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">NPSN</label>
            <input class="form-control @error('npsn') is-invalid @enderror" name="npsn" value="{{ $npsn }}" maxlength="8" inputmode="numeric">
            @error('npsn') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Tahun ajaran lulusan</label>
            <input class="form-control" name="tahun_ajaran_kelulusan" value="{{ $tahunAjaranLulusan }}" placeholder="2023/2024">
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

<div class="madani-card p-4">
    <div class="stat-label mb-3">Data pada ijazah</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nama</label>
            <input class="form-control bg-light" value="{{ $siswa->nama ?: '—' }}" readonly>
        </div>
        <div class="col-md-6">
            <label class="form-label">NISN</label>
            <input class="form-control bg-light" value="{{ $siswa->nisn ?: '—' }}" readonly>
        </div>
        <div class="col-md-4">
            <label class="form-label">Tempat lahir</label>
            <input class="form-control bg-light" value="{{ $siswa->tempat_lahir ?: '—' }}" readonly>
        </div>
        <div class="col-md-4">
            <label class="form-label">Tanggal lahir</label>
            <input class="form-control bg-light" value="{{ optional($siswa->tanggal_lahir)->format('d/m/Y') ?: '—' }}" readonly>
        </div>
        <div class="col-md-4">
            <label class="form-label">Jenis kelamin</label>
            <input class="form-control bg-light" value="{{ $jkLabel }}" readonly>
        </div>
        <div class="col-md-6">
            <label class="form-label">Nama ayah kandung</label>
            <input class="form-control bg-light" value="{{ $namaAyah ?: '—' }}" readonly>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="ijazah_sesuai" value="1" id="ijazah_sesuai" @checked($ijazahSesuai)>
                <label class="form-check-label" for="ijazah_sesuai">Sesuai</label>
            </div>
            <div class="form-text">Jika terdapat Data yang tidak sesuai silahkan perbaiki/hubungi operator madrasah.</div>
        </div>
    </div>
</div>
