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

    $jkLabel = $siswa->jenis_kelamin === 'P' ? 'Perempuan' : ($siswa->jenis_kelamin === 'L' ? 'Laki-laki' : '—');
    $namaAyah = $ayah?->nama ?: $rd?->nama_ayah_ijazah ?: $rd?->nama_ayah_kk;

    $ijazahItems = [
        ['key' => 'nama', 'label' => 'Nama', 'value' => $siswa->nama ?: '—', 'col' => 'col-md-6'],
        ['key' => 'nisn', 'label' => 'NISN', 'value' => $siswa->nisn ?: '—', 'col' => 'col-md-6'],
        ['key' => 'tempat_lahir', 'label' => 'Tempat lahir', 'value' => $siswa->tempat_lahir ?: '—', 'col' => 'col-md-4'],
        ['key' => 'tanggal_lahir', 'label' => 'Tanggal lahir', 'value' => optional($siswa->tanggal_lahir)->format('d/m/Y') ?: '—', 'col' => 'col-md-4'],
        ['key' => 'jenis_kelamin', 'label' => 'Jenis kelamin', 'value' => $jkLabel, 'col' => 'col-md-4'],
        ['key' => 'nama_ayah', 'label' => 'Nama ayah kandung', 'value' => $namaAyah ?: '—', 'col' => 'col-md-6'],
    ];
    $sesuaiFields = old('ijazah_sesuai', $rd?->ijazah_sesuai_fields ?? []);
    $sesuaiFields = is_array($sesuaiFields) ? $sesuaiFields : [];
    if ($sesuaiFields === [] && $rd?->ijazah_sesuai) {
        $sesuaiFields = array_column($ijazahItems, 'key');
    }
@endphp

<div class="madani-card p-4 mb-3">
    <div class="stat-label mb-3">Data jenjang sebelumnya</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nama sekolah <span class="text-danger">*</span></label>
            <input class="form-control @error('nama_sd') is-invalid @enderror" name="nama_sd" value="{{ $namaSekolah }}" required>
            @error('nama_sd') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">NPSN <span class="text-danger">*</span></label>
            <input class="form-control @error('npsn') is-invalid @enderror" name="npsn" value="{{ $npsn }}" maxlength="8" inputmode="numeric" data-angka required>
            @error('npsn') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Tahun ajaran lulusan <span class="text-danger">*</span></label>
            <input class="form-control @error('tahun_ajaran_kelulusan') is-invalid @enderror" name="tahun_ajaran_kelulusan" value="{{ $tahunAjaranLulusan }}" placeholder="2023/2024" required>
            @error('tahun_ajaran_kelulusan') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">NIP kepala sekolah <span class="text-danger">*</span></label>
            <input class="form-control @error('nip_kepala_sekolah') is-invalid @enderror" name="nip_kepala_sekolah" value="{{ $nipKepala }}" maxlength="18" inputmode="numeric" data-angka required>
            @error('nip_kepala_sekolah') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-8">
            <label class="form-label">Nama kepala sekolah <span class="text-danger">*</span></label>
            <input class="form-control @error('nama_kepala_sekolah') is-invalid @enderror" name="nama_kepala_sekolah" value="{{ $namaKepala }}" data-nama-orang required>
            @error('nama_kepala_sekolah') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Nomor seri ijazah <span class="text-danger">*</span></label>
            <input class="form-control @error('nomor_seri_ijazah') is-invalid @enderror" name="nomor_seri_ijazah" value="{{ $nomorSeri }}" maxlength="50" required>
            @error('nomor_seri_ijazah') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Tanggal terbit ijazah <span class="text-danger">*</span></label>
            <input class="form-control @error('tanggal_terbit_ijazah') is-invalid @enderror" type="date" name="tanggal_terbit_ijazah" value="{{ $tanggalTerbit }}" required>
            @error('tanggal_terbit_ijazah') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <x-dokumen-box
                judul="Ijazah"
                name="file_ijazah"
                jenis="ijazah_sd"
                :dokumen="$ijazahSd"
                :siswa="($portal ?? false) ? null : $siswa"
                :required="! $ijazahSd"
                hint="Maks. 1MB · pdf / jpg / png. Wajib diunggah."
            />
        </div>
    </div>
</div>

<div class="madani-card p-4">
    <div class="stat-label mb-3">Data pada ijazah</div>
    <div class="row g-3">
        @foreach ($ijazahItems as $item)
            <div class="{{ $item['col'] }}">
                <label class="form-label" for="ijazah_sesuai_{{ $item['key'] }}">{{ $item['label'] }}</label>
                <div class="input-group">
                    <input class="form-control bg-light" value="{{ $item['value'] }}" readonly>
                    <div class="input-group-text gap-2">
                        <input
                            class="form-check-input mt-0"
                            type="checkbox"
                            name="ijazah_sesuai[]"
                            value="{{ $item['key'] }}"
                            id="ijazah_sesuai_{{ $item['key'] }}"
                            @checked(in_array($item['key'], $sesuaiFields, true))
                            data-ijazah-sesuai
                        >
                        <label class="form-check-label" for="ijazah_sesuai_{{ $item['key'] }}">Sesuai</label>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="col-12">
            <div class="form-text">Jika terdapat Data yang tidak sesuai silahkan perbaiki/hubungi operator madrasah.</div>
        </div>
    </div>
</div>
