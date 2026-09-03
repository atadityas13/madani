@php
    $s = $siswa ?? null;
    $p = $periodik ?? null;
    $kunci = ($portal ?? false) && $s;
    $dataSiswaSelesai = $s
        ? (bool) (collect($s->kelengkapan()['tab'])->firstWhere('id', 'data-siswa')['selesai'] ?? false)
        : false;
    $pengajuanPending = $s
        ? $s->pengajuanPerubahans->where('status', 'pending')->keyBy('field')
        : collect();
    $imunisasiTerpilih = old('imunisasi', $p?->imunisasi ?? []);
    $disabilitasTerpilih = old('disabilitas', $p?->disabilitas ?? []);
    $disabilitasTerpilih = is_array($disabilitasTerpilih) ? $disabilitasTerpilih : [$disabilitasTerpilih];
    $valAgama = old('agama', $s?->agama);
    $kebutuhanKhusus = old('kebutuhan_khusus', $p?->kebutuhanKhususLabel());
    $dokumenKk = $s?->dokumenJenis('kk');
    $dokumenAkta = $s?->dokumenJenis('akta_lahir');
    $dokumenKip = $s?->dokumenJenis('kip');
    $jenisKelaminLabel = old('jenis_kelamin', $s?->jenis_kelamin) === 'P' ? 'Perempuan' : (old('jenis_kelamin', $s?->jenis_kelamin) === 'L' ? 'Laki-laki' : '');
@endphp

@php
    $tombolAjukan = function (string $field, string $label, ?string $nilai) use ($kunci, $dataSiswaSelesai, $pengajuanPending) {
        if (! $kunci) {
            return '';
        }
        $pending = $pengajuanPending->get($field);
        $attrs = 'type="button" class="btn btn-outline-secondary" data-ajukan-field="'.e($field).'" data-ajukan-label="'.e($label).'" data-ajukan-nilai="'.e((string) $nilai).'"';
        if (! $dataSiswaSelesai) {
            $attrs .= ' data-ajukan-terkunci';
        }
        $title = $pending ? 'Menunggu konfirmasi madrasah' : 'Ajukan perubahan';

        return '<button '.$attrs.' title="'.e($title).'"><span class="ajukan-identitas-icon"><i class="bi bi-arrow-repeat"></i><i class="bi bi-pencil-fill"></i></span></button>';
    };
@endphp

<div class="row g-3" data-form-data-siswa>
    <div class="col-12">
        <div class="stat-label">Identitas</div>
    </div>
    <div class="col-md-8">
        <label class="form-label">Nama lengkap</label>
        <div class="input-group">
            <input class="form-control @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama', $s?->nama) }}" data-nama-orang @if ($kunci) readonly @else required @endif>
            {!! $tombolAjukan('nama', 'Nama lengkap', $s?->nama) !!}
        </div>
        @error('nama') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @if ($pengajuanPending->has('nama'))
            <div class="form-text text-warning">Menunggu konfirmasi madrasah</div>
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label">Jenis kelamin</label>
        @if ($kunci)
            <input type="hidden" name="jenis_kelamin" value="{{ $s?->jenis_kelamin }}">
            <div class="input-group">
                <input class="form-control" value="{{ $jenisKelaminLabel }}" readonly>
                {!! $tombolAjukan('jenis_kelamin', 'Jenis kelamin', $s?->jenis_kelamin) !!}
            </div>
        @else
            <select class="form-select @error('jenis_kelamin') is-invalid @enderror" name="jenis_kelamin" required>
                <option value="">Pilih</option>
                <option value="L" @selected(old('jenis_kelamin', $s?->jenis_kelamin) === 'L')>Laki-laki</option>
                <option value="P" @selected(old('jenis_kelamin', $s?->jenis_kelamin) === 'P')>Perempuan</option>
            </select>
            @error('jenis_kelamin') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @endif
        @if ($pengajuanPending->has('jenis_kelamin'))
            <div class="form-text text-warning">Menunggu konfirmasi madrasah</div>
        @endif
    </div>

    <div class="col-md-4">
        <label class="form-label">NISN</label>
        <div class="input-group">
            <input class="form-control @error('nisn') is-invalid @enderror" name="nisn" value="{{ old('nisn', $s?->nisn) }}" maxlength="10" inputmode="numeric" data-angka @if ($kunci) readonly @else required @endif>
            {!! $tombolAjukan('nisn', 'NISN', $s?->nisn) !!}
        </div>
        @error('nisn') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @if ($pengajuanPending->has('nisn'))
            <div class="form-text text-warning">Menunggu konfirmasi madrasah</div>
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label">NIS Lokal</label>
        <input class="form-control" name="nis" value="{{ old('nis', $s?->nis) }}" maxlength="20" inputmode="numeric" data-angka @if ($kunci) readonly @endif>
    </div>
    <div class="col-md-4">
        <label class="form-label">NIK</label>
        <div class="input-group">
            <input class="form-control @error('nik') is-invalid @enderror" name="nik" value="{{ old('nik', $s?->nik) }}" maxlength="16" inputmode="numeric" data-angka @if ($kunci) readonly @else required @endif>
            {!! $tombolAjukan('nik', 'NIK', $s?->nik) !!}
        </div>
        @error('nik') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @if ($pengajuanPending->has('nik'))
            <div class="form-text text-warning">Menunggu konfirmasi madrasah</div>
        @endif
    </div>

    <div class="col-md-6">
        <label class="form-label">Tempat lahir</label>
        <div class="input-group">
            <input class="form-control @error('tempat_lahir') is-invalid @enderror" name="tempat_lahir" value="{{ old('tempat_lahir', $s?->tempat_lahir) }}" @if ($kunci) readonly @else required @endif>
            {!! $tombolAjukan('tempat_lahir', 'Tempat lahir', $s?->tempat_lahir) !!}
        </div>
        @error('tempat_lahir') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @if ($pengajuanPending->has('tempat_lahir'))
            <div class="form-text text-warning">Menunggu konfirmasi madrasah</div>
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label">Tanggal lahir</label>
        <div class="input-group">
            <input class="form-control @error('tanggal_lahir') is-invalid @enderror" type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', optional($s?->tanggal_lahir)->format('Y-m-d')) }}" @if ($kunci) readonly @else required @endif>
            {!! $tombolAjukan('tanggal_lahir', 'Tanggal lahir', optional($s?->tanggal_lahir)->format('Y-m-d')) !!}
        </div>
        @error('tanggal_lahir') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @if ($pengajuanPending->has('tanggal_lahir'))
            <div class="form-text text-warning">Menunggu konfirmasi madrasah</div>
        @endif
    </div>

    <div class="col-md-3">
        <label class="form-label">Jumlah saudara <span class="text-danger">*</span></label>
        <input class="form-control @error('jumlah_saudara') is-invalid @enderror" type="number" min="0" name="jumlah_saudara" value="{{ old('jumlah_saudara', $s?->jumlah_saudara) }}" required>
        @error('jumlah_saudara') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Anak ke <span class="text-danger">*</span></label>
        <input class="form-control @error('anak_ke') is-invalid @enderror" type="number" min="1" name="anak_ke" value="{{ old('anak_ke', $s?->anak_ke) }}" required>
        @error('anak_ke') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Agama <span class="text-danger">*</span></label>
        <x-emis-select name="agama" :options="$emis['agama']" :value="$valAgama" required />
        @error('agama') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Cita-cita <span class="text-danger">*</span></label>
        <x-emis-select name="cita_cita" :options="$emis['cita_cita']" :value="old('cita_cita', $s?->cita_cita)" required />
        @error('cita_cita') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Hobi <span class="text-danger">*</span></label>
        <x-emis-select name="hobi" :options="$emis['hobi']" :value="old('hobi', $s?->hobi)" required />
        @error('hobi') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 pt-2">
        <div class="stat-label">Kontak</div>
    </div>
    <div class="col-md-6" data-kontak-bypass>
        <label class="form-label">Nomor HP/Whatsapp <span class="text-danger">*</span></label>
        <input class="form-control @error('no_hp') is-invalid @enderror" name="no_hp" value="{{ old('no_hp', $s?->no_hp) }}" placeholder="contoh: 6282372377723" maxlength="17" inputmode="numeric" data-nomor data-hp>
        @error('no_hp') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-check mt-1">
            <input class="form-check-input" type="checkbox" name="tidak_punya_hp" value="1" id="tidak_punya_hp" @checked(old('tidak_punya_hp', $s?->tidak_punya_hp)) data-tidak-punya>
            <label class="form-check-label small" for="tidak_punya_hp">Tidak memiliki nomor HP/Whatsapp</label>
        </div>
    </div>
    <div class="col-md-6" data-kontak-bypass>
        <label class="form-label">Alamat email siswa <span class="text-danger">*</span></label>
        <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $s?->email) }}" data-nomor>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-check mt-1">
            <input class="form-check-input" type="checkbox" name="tidak_punya_email" value="1" id="tidak_punya_email" @checked(old('tidak_punya_email', $s?->tidak_punya_email)) data-tidak-punya>
            <label class="form-check-label small" for="tidak_punya_email">Tidak memiliki email</label>
        </div>
    </div>

    <div class="col-12 pt-2">
        <div class="stat-label">Pra sekolah</div>
    </div>
    <div class="col-12">
        <div class="d-flex gap-3">
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

    <div class="col-12 pt-2">
        <div class="stat-label">Imunisasi</div>
        <div class="d-flex flex-wrap gap-3">
            @foreach ($emis['imunisasi'] as $key => $label)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="imunisasi[]" value="{{ $key }}" id="imun_{{ $key }}" @checked(in_array($key, $imunisasiTerpilih, true))>
                    <label class="form-check-label" for="imun_{{ $key }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-12 pt-2">
        <div class="stat-label">Kebutuhan Khusus &amp; Disabilitas</div>
    </div>
    <div class="col-md-6" data-kebutuhan-khusus>
        <label class="form-label">Kebutuhan khusus <span class="text-danger">*</span></label>
        <x-emis-select name="kebutuhan_khusus" :options="$emis['kebutuhan_khusus']" :value="$kebutuhanKhusus" data-kebutuhan-khusus-select required />
        @error('kebutuhan_khusus') <div class="text-danger small">{{ $message }}</div> @enderror
        <div class="mt-2" data-kebutuhan-khusus-lainnya @if ($kebutuhanKhusus !== 'Lainnya') hidden @endif>
            <input class="form-control @error('kebutuhan_khusus_lainnya') is-invalid @enderror" name="kebutuhan_khusus_lainnya" value="{{ old('kebutuhan_khusus_lainnya', $p?->kebutuhan_khusus_lainnya) }}" placeholder="Sebutkan kebutuhan khusus lainnya">
            @error('kebutuhan_khusus_lainnya') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="col-12" data-disabilitas>
        <label class="form-label">Disabilitas</label>
        <div class="row g-2">
            @foreach ($emis['disabilitas'] as $key => $label)
                <div class="col-6 col-md-3">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="disabilitas[]"
                            value="{{ $key }}"
                            id="disabilitas_{{ \Illuminate\Support\Str::slug($key) }}"
                            @checked(in_array($key, $disabilitasTerpilih, true))
                            data-disabilitas-item
                        >
                        <label class="form-check-label" for="disabilitas_{{ \Illuminate\Support\Str::slug($key) }}">{{ $label }}</label>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-2" data-disabilitas-lainnya @if (! in_array('Lainnya', $disabilitasTerpilih, true)) hidden @endif>
            <input class="form-control" name="disabilitas_lainnya" value="{{ old('disabilitas_lainnya', $p?->disabilitas_lainnya) }}" placeholder="Sebutkan disabilitas lainnya" style="max-width: 360px;">
        </div>
    </div>

    <div class="col-12 pt-2">
        <div class="stat-label">Keluarga</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Nomor KK <span class="text-danger">*</span></label>
        <input class="form-control @error('no_kk') is-invalid @enderror" name="no_kk" value="{{ old('no_kk', $p?->no_kk) }}" maxlength="16" inputmode="numeric" data-angka required>
        @error('no_kk') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Nama kepala keluarga <span class="text-danger">*</span></label>
        <input class="form-control @error('kepala_keluarga') is-invalid @enderror" name="kepala_keluarga" value="{{ old('kepala_keluarga', $p?->kepala_keluarga) }}" data-nama-orang required>
        @error('kepala_keluarga') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Yang membiayai sekolah <span class="text-danger">*</span></label>
        <x-emis-select name="pembiaya" :options="$emis['pembiaya']" :value="old('pembiaya', $p?->pembiaya)" required />
        @error('pembiaya') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6" data-bantuan-kartu="kip">
        <label class="form-label">Nomor KIP <span class="text-danger">*</span></label>
        <input class="form-control @error('no_kip') is-invalid @enderror" name="no_kip" value="{{ old('no_kip', $p?->no_kip) }}" data-nomor>
        @error('no_kip') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="tidak_punya_kip" value="1" id="tidak_punya_kip" @checked(old('tidak_punya_kip', $p?->tidak_punya_kip)) data-tidak-punya>
            <label class="form-check-label small" for="tidak_punya_kip">Tidak memiliki KIP</label>
        </div>
    </div>

    <div class="col-12 pt-2">
        <div class="stat-label">Dokumen</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Unggah Kartu Keluarga <span class="text-danger">*</span></label>
        <div class="form-text">Wajib. Maks. 1MB bertipe pdf jpg png</div>
        @if ($dokumenKk)
            <div class="form-text">Berkas tersimpan: {{ $dokumenKk->nama_asli }}</div>
        @endif
        <input class="form-control mt-1 @error('file_kk') is-invalid @enderror" type="file" name="file_kk" accept=".pdf,.jpg,.jpeg,.png">
        @error('file_kk') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Unggah Akta Kelahiran <span class="text-danger">*</span></label>
        <div class="form-text">Wajib. Maks. 1MB bertipe pdf jpg png</div>
        @if ($dokumenAkta)
            <div class="form-text">Berkas tersimpan: {{ $dokumenAkta->nama_asli }}</div>
        @endif
        <input class="form-control mt-1 @error('file_akta') is-invalid @enderror" type="file" name="file_akta" accept=".pdf,.jpg,.jpeg,.png">
        @error('file_akta') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6" data-kip-upload @if (blank(old('no_kip', $p?->no_kip)) || old('tidak_punya_kip', $p?->tidak_punya_kip)) hidden @endif>
        <label class="form-label">Unggah kartu KIP <span class="text-danger">*</span></label>
        <div class="form-text">Wajib jika nomor KIP diisi. Maks. 1MB bertipe pdf jpg png</div>
        @if ($dokumenKip)
            <div class="form-text">Berkas tersimpan: {{ $dokumenKip->nama_asli }}</div>
        @endif
        <input class="form-control mt-1 @error('file_kip') is-invalid @enderror" type="file" name="file_kip" accept=".pdf,.jpg,.jpeg,.png" data-berkas>
        @error('file_kip') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
</div>
