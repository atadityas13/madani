@php
    $s = $siswa ?? null;
    $p = $periodik ?? null;
    $imunisasiTerpilih = old('imunisasi', $p?->imunisasi ?? []);
    $valAgama = old('agama', $s?->agama ?? 'Islam');
@endphp
<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">Nama lengkap</label>
        <input class="form-control @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama', $s?->nama) }}" required>
        @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Jenis kelamin</label>
        <select class="form-select @error('jenis_kelamin') is-invalid @enderror" name="jenis_kelamin" required>
            <option value="">Pilih</option>
            <option value="L" @selected(old('jenis_kelamin', $s?->jenis_kelamin) === 'L')>Laki-laki</option>
            <option value="P" @selected(old('jenis_kelamin', $s?->jenis_kelamin) === 'P')>Perempuan</option>
        </select>
        @error('jenis_kelamin') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">NISN</label>
        <input class="form-control @error('nisn') is-invalid @enderror" name="nisn" value="{{ old('nisn', $s?->nisn) }}" maxlength="10" inputmode="numeric" required @disabled($s?->nisn)>
        @if ($s?->nisn)
            <input type="hidden" name="nisn" value="{{ $s->nisn }}">
        @endif
        @error('nisn') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @if ($s?->nisn)
            <div class="form-text">NISN dikunci setelah tersimpan, seperti di EMIS.</div>
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label">NIS Lokal</label>
        <input class="form-control" name="nis" value="{{ old('nis', $s?->nis) }}" maxlength="20">
    </div>
    <div class="col-md-4">
        <label class="form-label">NIK</label>
        <input class="form-control @error('nik') is-invalid @enderror" name="nik" value="{{ old('nik', $s?->nik) }}" maxlength="16" inputmode="numeric" required>
        @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Tempat lahir</label>
        <input class="form-control @error('tempat_lahir') is-invalid @enderror" name="tempat_lahir" value="{{ old('tempat_lahir', $s?->tempat_lahir) }}" required>
        @error('tempat_lahir') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Tanggal lahir</label>
        <input class="form-control @error('tanggal_lahir') is-invalid @enderror" type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', optional($s?->tanggal_lahir)->format('Y-m-d')) }}" required>
        @error('tanggal_lahir') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Jumlah saudara</label>
        <input class="form-control @error('jumlah_saudara') is-invalid @enderror" type="number" min="0" name="jumlah_saudara" value="{{ old('jumlah_saudara', $s?->jumlah_saudara) }}" required>
        @error('jumlah_saudara') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Anak ke</label>
        <input class="form-control @error('anak_ke') is-invalid @enderror" type="number" min="1" name="anak_ke" value="{{ old('anak_ke', $s?->anak_ke) }}" required>
        @error('anak_ke') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Agama</label>
        <x-emis-select name="agama" :options="$emis['agama']" :value="$valAgama" required />
        @error('agama') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Cita cita</label>
        <x-emis-select name="cita_cita" :options="$emis['cita_cita']" :value="old('cita_cita', $s?->cita_cita)" required />
        @error('cita_cita') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Hobi</label>
        <x-emis-select name="hobi" :options="$emis['hobi']" :value="old('hobi', $s?->hobi)" />
    </div>

    <div class="col-md-6">
        <label class="form-label">Nomor handphone</label>
        <input class="form-control @error('no_hp') is-invalid @enderror" name="no_hp" value="{{ old('no_hp', $s?->no_hp) }}" placeholder="contoh: 6282372377723">
        @error('no_hp') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-check mt-1">
            <input class="form-check-input" type="checkbox" name="tidak_punya_hp" value="1" id="tidak_punya_hp" @checked(old('tidak_punya_hp', $s?->tidak_punya_hp))>
            <label class="form-check-label small" for="tidak_punya_hp">Tidak memiliki nomor handphone</label>
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Alamat email siswa</label>
        <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $s?->email) }}">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Yang membiayai sekolah</label>
        <x-emis-select name="pembiaya" :options="$emis['pembiaya']" :value="old('pembiaya', $p?->pembiaya)" required />
        @error('pembiaya') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Pra sekolah</label>
        <div class="d-flex gap-3 pt-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="pernah_tk_ra" value="1" id="pernah_tk_ra" @checked(old('pernah_tk_ra', $p?->pernah_tk_ra))>
                <label class="form-check-label" for="pernah_tk_ra">Pernah TK/RA</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="pernah_paud" value="1" id="pernah_paud" @checked(old('pernah_paud', $p?->pernah_paud))>
                <label class="form-check-label" for="pernah_paud">Pernah PAUD</label>
            </div>
        </div>
    </div>

    <div class="col-12">
        <label class="form-label">Imunisasi</label>
        <div class="d-flex flex-wrap gap-3">
            @foreach ($emis['imunisasi'] as $key => $label)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="imunisasi[]" value="{{ $key }}" id="imun_{{ $key }}" @checked(in_array($key, $imunisasiTerpilih, true))>
                    <label class="form-check-label" for="imun_{{ $key }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-md-4">
        <label class="form-label">Nomor KIP</label>
        <input class="form-control" name="no_kip" value="{{ old('no_kip', $p?->no_kip) }}">
        <div class="form-text">Unggah KIP maks. 2MB bertipe pdf jpg png</div>
        <input class="form-control mt-1" type="file" name="file_kip" accept=".pdf,.jpg,.jpeg,.png">
    </div>
    <div class="col-md-4">
        <label class="form-label">No KK</label>
        <input class="form-control @error('no_kk') is-invalid @enderror" name="no_kk" value="{{ old('no_kk', $p?->no_kk) }}" maxlength="16" inputmode="numeric">
        @error('no_kk') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Nama kepala keluarga</label>
        <input class="form-control" name="kepala_keluarga" value="{{ old('kepala_keluarga', $p?->kepala_keluarga) }}">
        <div class="form-text">Unggah Kartu Keluarga maks. 2MB bertipe pdf jpg png</div>
        <input class="form-control mt-1" type="file" name="file_kk" accept=".pdf,.jpg,.jpeg,.png">
    </div>
</div>
