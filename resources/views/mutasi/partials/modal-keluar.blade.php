@php
    $isOld = old('_mutasi_form') === 'keluar';
@endphp
<div class="modal fade" id="mutasiKeluarModal" tabindex="-1" aria-labelledby="mutasiKeluarModalLabel" aria-hidden="true" @if ($bukaModal) data-modal-open @endif>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('mutasi.keluar.store') }}" data-mutasi-nonaktif-form>
                @csrf
                <input type="hidden" name="_mutasi_form" value="keluar">
                <div class="modal-header">
                    <h5 class="modal-title stat-label mb-0" id="mutasiKeluarModalLabel">Tambah mutasi keluar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="stat-label mb-3">Pilih siswa</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label" for="keluar_tingkat">Tingkat</label>
                            <select class="form-select" id="keluar_tingkat" name="tingkat_filter" data-tingkat-select>
                                <option value="">Pilih tingkat</option>
                                @foreach ($tingkatOptions as $option)
                                    <option value="{{ $option }}" @selected($isOld && old('tingkat_filter') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="keluar_siswa_cari">Siswa</label>
                            @include('mutasi.partials.siswa-combobox', [
                                'prefix' => 'keluar',
                                'siswaId' => $isOld ? old('siswa_id') : '',
                                'siswaLabel' => $isOld ? old('siswa_label') : '',
                                'invalid' => $isOld && $errors->has('siswa_id'),
                            ])
                            @if ($isOld)
                                @error('siswa_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>
                    </div>

                    <div class="row g-3 mb-4" data-siswa-detail hidden>
                        <div class="col-md-4">
                            <label class="form-label">NISN</label>
                            <input class="form-control bg-light" data-detail-nisn readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rombel</label>
                            <input class="form-control bg-light" data-detail-rombel readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nama wali</label>
                            <input class="form-control bg-light" data-detail-wali readonly>
                        </div>
                    </div>

                    <div class="stat-label mb-3">Sekolah tujuan</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="keluar_jenis_sekolah">Jenis sekolah</label>
                            <select class="form-select @if ($isOld && $errors->has('jenis_sekolah')) is-invalid @endif" id="keluar_jenis_sekolah" name="jenis_sekolah" required data-emis-toggle>
                                <option value="">Pilih</option>
                                @foreach ($jenisSekolahOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($isOld && old('jenis_sekolah') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if ($isOld)
                                @error('jenis_sekolah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-4" data-emis-wrap @style(['display: none' => ! $isOld || old('jenis_sekolah', '') !== 'madrasah'])>
                            <label class="form-label" for="keluar_nomor_dokumen_emis">Nomor dokumen EMIS</label>
                            <input class="form-control @if ($isOld && $errors->has('nomor_dokumen_emis')) is-invalid @endif" id="keluar_nomor_dokumen_emis" name="nomor_dokumen_emis" value="{{ $isOld ? old('nomor_dokumen_emis') : '' }}" maxlength="50" data-emis-input>
                            @if ($isOld)
                                @error('nomor_dokumen_emis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="keluar_nama_sekolah">Nama sekolah tujuan</label>
                            <input class="form-control @if ($isOld && $errors->has('nama_sekolah')) is-invalid @endif" id="keluar_nama_sekolah" name="nama_sekolah" value="{{ $isOld ? old('nama_sekolah') : '' }}" required maxlength="150">
                            @if ($isOld)
                                @error('nama_sekolah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="keluar_alasan">Alasan</label>
                            <select class="form-select @if ($isOld && $errors->has('alasan')) is-invalid @endif" id="keluar_alasan" name="alasan" required>
                                <option value="">Pilih</option>
                                @foreach ($alasanOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($isOld && old('alasan') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if ($isOld)
                                @error('alasan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="keluar_tanggal">Tanggal mutasi</label>
                            <input class="form-control @if ($isOld && $errors->has('tanggal')) is-invalid @endif" type="date" id="keluar_tanggal" name="tanggal" value="{{ $isOld ? old('tanggal', now()->toDateString()) : now()->toDateString() }}">
                            @if ($isOld)
                                @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
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
