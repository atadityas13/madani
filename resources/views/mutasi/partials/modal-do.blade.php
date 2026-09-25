@php
    $isOld = old('_mutasi_form') === 'do';
@endphp
<div class="modal fade" id="mutasiDoModal" tabindex="-1" aria-labelledby="mutasiDoModalLabel" aria-hidden="true" @if ($bukaModal) data-modal-open @endif>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('mutasi.do.store') }}" data-mutasi-nonaktif-form>
                @csrf
                <input type="hidden" name="_mutasi_form" value="do">
                <div class="modal-header">
                    <h5 class="modal-title stat-label mb-0" id="mutasiDoModalLabel">Tambah dropout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="stat-label mb-3">Pilih siswa</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label" for="do_tingkat">Tingkat</label>
                            <select class="form-select" id="do_tingkat" name="tingkat_filter" data-tingkat-select>
                                <option value="">Pilih tingkat</option>
                                @foreach ($tingkatOptions as $option)
                                    <option value="{{ $option }}" @selected($isOld && old('tingkat_filter') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="do_siswa_cari">Siswa</label>
                            @include('mutasi.partials.siswa-combobox', [
                                'prefix' => 'do',
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

                    <div class="stat-label mb-3">Data dropout</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="do_alasan">Alasan</label>
                            <select class="form-select @if ($isOld && $errors->has('alasan')) is-invalid @endif" id="do_alasan" name="alasan" required>
                                <option value="">Pilih</option>
                                @foreach ($alasanOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($isOld && old('alasan') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if ($isOld)
                                @error('alasan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="do_tanggal">Tanggal</label>
                            <input class="form-control @if ($isOld && $errors->has('tanggal')) is-invalid @endif" type="date" id="do_tanggal" name="tanggal" value="{{ $isOld ? old('tanggal', now()->toDateString()) : now()->toDateString() }}">
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
