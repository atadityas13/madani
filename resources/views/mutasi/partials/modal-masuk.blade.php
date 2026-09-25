@php
    $isOld = old('_mutasi_form') === 'masuk';
    $oldVal = fn (string $key, mixed $default = null) => $isOld ? old($key, $default) : $default;
    $recordWilayah = (object) [
        'provinsi' => $oldVal('provinsi'),
        'kota' => $oldVal('kota'),
        'kecamatan' => $oldVal('kecamatan'),
        'desa' => $oldVal('desa'),
        'blok' => $oldVal('blok'),
        'rt' => $oldVal('rt'),
        'rw' => $oldVal('rw'),
        'kode_pos' => $oldVal('kode_pos'),
        'alamat' => $oldVal('alamat'),
    ];
@endphp

<div class="modal fade" id="mutasiMasukModal" tabindex="-1" aria-labelledby="mutasiMasukModalLabel" aria-hidden="true" @if ($bukaModal) data-modal-open @endif>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('mutasi.masuk.store') }}">
                @csrf
                <input type="hidden" name="_mutasi_form" value="masuk">
                <div class="modal-header">
                    <h5 class="modal-title stat-label mb-0" id="mutasiMasukModalLabel">Tambah mutasi masuk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="stat-label mb-3">Sekolah asal</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label" for="masuk_jenis_sekolah">Jenis sekolah</label>
                            <select class="form-select @if ($isOld && $errors->has('jenis_sekolah')) is-invalid @endif" id="masuk_jenis_sekolah" name="jenis_sekolah" required data-emis-toggle>
                                <option value="">Pilih</option>
                                @foreach ($jenisSekolahOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($oldVal('jenis_sekolah') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if ($isOld)
                                @error('jenis_sekolah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-4" data-emis-wrap @style(['display: none' => $oldVal('jenis_sekolah', '') !== 'madrasah'])>
                            <label class="form-label" for="masuk_nomor_dokumen_emis">Nomor dokumen EMIS</label>
                            <input class="form-control @if ($isOld && $errors->has('nomor_dokumen_emis')) is-invalid @endif" id="masuk_nomor_dokumen_emis" name="nomor_dokumen_emis" value="{{ $oldVal('nomor_dokumen_emis') }}" maxlength="50" data-emis-input>
                            @if ($isOld)
                                @error('nomor_dokumen_emis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="masuk_nama_sekolah">Nama sekolah asal</label>
                            <input class="form-control @if ($isOld && $errors->has('nama_sekolah')) is-invalid @endif" id="masuk_nama_sekolah" name="nama_sekolah" value="{{ $oldVal('nama_sekolah') }}" required maxlength="150">
                            @if ($isOld)
                                @error('nama_sekolah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="masuk_alasan">Alasan</label>
                            <select class="form-select @if ($isOld && $errors->has('alasan')) is-invalid @endif" id="masuk_alasan" name="alasan" required>
                                <option value="">Pilih</option>
                                @foreach ($alasanOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($oldVal('alasan') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if ($isOld)
                                @error('alasan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="masuk_tanggal">Tanggal mutasi</label>
                            <input class="form-control @if ($isOld && $errors->has('tanggal')) is-invalid @endif" type="date" id="masuk_tanggal" name="tanggal" value="{{ $oldVal('tanggal', now()->toDateString()) }}">
                            @if ($isOld)
                                @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                    </div>

                    <div class="stat-label mb-3">Data siswa</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="masuk_nama">Nama</label>
                            <input class="form-control @if ($isOld && $errors->has('nama')) is-invalid @endif" id="masuk_nama" name="nama" value="{{ $oldVal('nama') }}" required maxlength="150">
                            @if ($isOld)
                                @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="masuk_nisn">NISN</label>
                            <input class="form-control @if ($isOld && $errors->has('nisn')) is-invalid @endif" id="masuk_nisn" name="nisn" value="{{ $oldVal('nisn') }}" required maxlength="10" inputmode="numeric">
                            @if ($isOld)
                                @error('nisn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="masuk_nik">NIK</label>
                            <input class="form-control @if ($isOld && $errors->has('nik')) is-invalid @endif" id="masuk_nik" name="nik" value="{{ $oldVal('nik') }}" required maxlength="16" inputmode="numeric">
                            @if ($isOld)
                                @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="masuk_tempat_lahir">Tempat lahir</label>
                            <input class="form-control @if ($isOld && $errors->has('tempat_lahir')) is-invalid @endif" id="masuk_tempat_lahir" name="tempat_lahir" value="{{ $oldVal('tempat_lahir') }}" required maxlength="100">
                            @if ($isOld)
                                @error('tempat_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="masuk_tanggal_lahir">Tanggal lahir</label>
                            <input class="form-control @if ($isOld && $errors->has('tanggal_lahir')) is-invalid @endif" type="date" id="masuk_tanggal_lahir" name="tanggal_lahir" value="{{ $oldVal('tanggal_lahir') }}" required>
                            @if ($isOld)
                                @error('tanggal_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="masuk_jenis_kelamin">Jenis kelamin</label>
                            <select class="form-select @if ($isOld && $errors->has('jenis_kelamin')) is-invalid @endif" id="masuk_jenis_kelamin" name="jenis_kelamin" required>
                                <option value="">Pilih</option>
                                <option value="L" @selected($oldVal('jenis_kelamin') === 'L')>Laki-laki</option>
                                <option value="P" @selected($oldVal('jenis_kelamin') === 'P')>Perempuan</option>
                            </select>
                            @if ($isOld)
                                @error('jenis_kelamin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="masuk_angkatan">Tingkat</label>
                            <select class="form-select @if ($isOld && $errors->has('angkatan')) is-invalid @endif" id="masuk_angkatan" name="angkatan" required>
                                <option value="">Pilih</option>
                                @foreach ($tingkatOptions as $tingkat)
                                    <option value="{{ $tingkat }}" @selected($oldVal('angkatan') === $tingkat)>{{ $tingkat }}</option>
                                @endforeach
                            </select>
                            @if ($isOld)
                                @error('angkatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                    </div>

                    <div class="stat-label mb-3">Wali / orang tua</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label" for="masuk_wali_dari">Wali</label>
                            <select class="form-select @if ($isOld && $errors->has('wali_dari')) is-invalid @endif" id="masuk_wali_dari" name="wali_dari" required>
                                <option value="">Pilih</option>
                                <option value="ayah" @selected($oldVal('wali_dari') === 'ayah')>Ayah</option>
                                <option value="ibu" @selected($oldVal('wali_dari') === 'ibu')>Ibu</option>
                                <option value="lainnya" @selected($oldVal('wali_dari') === 'lainnya')>Lainnya</option>
                            </select>
                            @if ($isOld)
                                @error('wali_dari')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="masuk_nama_ortu">Nama ortu / wali</label>
                            <input class="form-control @if ($isOld && $errors->has('nama_ortu')) is-invalid @endif" id="masuk_nama_ortu" name="nama_ortu" value="{{ $oldVal('nama_ortu') }}" required maxlength="150">
                            @if ($isOld)
                                @error('nama_ortu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="masuk_pekerjaan">Pekerjaan</label>
                            <select class="form-select @if ($isOld && $errors->has('pekerjaan')) is-invalid @endif" id="masuk_pekerjaan" name="pekerjaan" required>
                                <option value="">Pilih</option>
                                @foreach ($pekerjaanOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($oldVal('pekerjaan') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if ($isOld)
                                @error('pekerjaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="masuk_no_hp">Nomor HP</label>
                            <input class="form-control @if ($isOld && $errors->has('no_hp')) is-invalid @endif" id="masuk_no_hp" name="no_hp" value="{{ $oldVal('no_hp') }}" maxlength="20">
                            @if ($isOld)
                                @error('no_hp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                    </div>

                    <div class="stat-label mb-3">Alamat</div>
                    <div class="mb-1">
                        @include('siswa.partials.form-wilayah', [
                            'record' => $recordWilayah,
                            'root' => 'mutasi-masuk',
                            'wide' => true,
                        ])
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-madani" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
