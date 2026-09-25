@extends('layouts.app')

@section('title', 'Mutasi masuk')
@section('heading', 'Mutasi masuk')
@section('subheading', 'Siswa pindahan — status aktif tanpa rombel')

@section('content')
<div class="madani-card p-4">
    <form method="POST" action="{{ route('mutasi.masuk.store') }}">
        @csrf

        <div class="stat-label mb-3">Sekolah asal</div>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label" for="jenis_sekolah">Jenis sekolah</label>
                <select class="form-select @error('jenis_sekolah') is-invalid @enderror" id="jenis_sekolah" name="jenis_sekolah" required>
                    <option value="">Pilih</option>
                    @foreach ($jenisSekolahOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('jenis_sekolah') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('jenis_sekolah')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4" id="emis-wrap">
                <label class="form-label" for="nomor_dokumen_emis">Nomor dokumen EMIS</label>
                <input class="form-control @error('nomor_dokumen_emis') is-invalid @enderror" id="nomor_dokumen_emis" name="nomor_dokumen_emis" value="{{ old('nomor_dokumen_emis') }}" maxlength="50">
                @error('nomor_dokumen_emis')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="nama_sekolah">Nama sekolah asal</label>
                <input class="form-control @error('nama_sekolah') is-invalid @enderror" id="nama_sekolah" name="nama_sekolah" value="{{ old('nama_sekolah') }}" required maxlength="150">
                @error('nama_sekolah')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="alasan">Alasan</label>
                <select class="form-select @error('alasan') is-invalid @enderror" id="alasan" name="alasan" required>
                    <option value="">Pilih</option>
                    @foreach ($alasanOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('alasan') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('alasan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="tanggal">Tanggal mutasi</label>
                <input class="form-control @error('tanggal') is-invalid @enderror" type="date" id="tanggal" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}">
                @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="stat-label mb-3">Data siswa</div>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label" for="nama">Nama</label>
                <input class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama') }}" required maxlength="150">
                @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="nisn">NISN</label>
                <input class="form-control @error('nisn') is-invalid @enderror" id="nisn" name="nisn" value="{{ old('nisn') }}" required maxlength="10" inputmode="numeric">
                @error('nisn')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="nik">NIK</label>
                <input class="form-control @error('nik') is-invalid @enderror" id="nik" name="nik" value="{{ old('nik') }}" required maxlength="16" inputmode="numeric">
                @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="tempat_lahir">Tempat lahir</label>
                <input class="form-control @error('tempat_lahir') is-invalid @enderror" id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir') }}" required maxlength="100">
                @error('tempat_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="tanggal_lahir">Tanggal lahir</label>
                <input class="form-control @error('tanggal_lahir') is-invalid @enderror" type="date" id="tanggal_lahir" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" required>
                @error('tanggal_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="jenis_kelamin">Jenis kelamin</label>
                <select class="form-select @error('jenis_kelamin') is-invalid @enderror" id="jenis_kelamin" name="jenis_kelamin" required>
                    <option value="">Pilih</option>
                    <option value="L" @selected(old('jenis_kelamin') === 'L')>Laki-laki</option>
                    <option value="P" @selected(old('jenis_kelamin') === 'P')>Perempuan</option>
                </select>
                @error('jenis_kelamin')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="angkatan">Tingkat</label>
                <select class="form-select @error('angkatan') is-invalid @enderror" id="angkatan" name="angkatan" required>
                    <option value="">Pilih</option>
                    @foreach ($tingkatOptions as $tingkat)
                        <option value="{{ $tingkat }}" @selected(old('angkatan') === $tingkat)>{{ $tingkat }}</option>
                    @endforeach
                </select>
                @error('angkatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="stat-label mb-3">Wali / orang tua</div>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label" for="wali_dari">Wali</label>
                <select class="form-select @error('wali_dari') is-invalid @enderror" id="wali_dari" name="wali_dari" required>
                    <option value="">Pilih</option>
                    <option value="ayah" @selected(old('wali_dari') === 'ayah')>Ayah</option>
                    <option value="ibu" @selected(old('wali_dari') === 'ibu')>Ibu</option>
                    <option value="lainnya" @selected(old('wali_dari') === 'lainnya')>Lainnya</option>
                </select>
                @error('wali_dari')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-5">
                <label class="form-label" for="nama_ortu">Nama ortu / wali</label>
                <input class="form-control @error('nama_ortu') is-invalid @enderror" id="nama_ortu" name="nama_ortu" value="{{ old('nama_ortu') }}" required maxlength="150">
                @error('nama_ortu')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="pekerjaan">Pekerjaan</label>
                <select class="form-select @error('pekerjaan') is-invalid @enderror" id="pekerjaan" name="pekerjaan" required>
                    <option value="">Pilih</option>
                    @foreach ($pekerjaanOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('pekerjaan') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('pekerjaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="no_hp">Nomor HP</label>
                <input class="form-control @error('no_hp') is-invalid @enderror" id="no_hp" name="no_hp" value="{{ old('no_hp') }}" maxlength="20">
                @error('no_hp')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="stat-label mb-3">Alamat</div>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <label class="form-label" for="alamat">Alamat</label>
                <input class="form-control @error('alamat') is-invalid @enderror" id="alamat" name="alamat" value="{{ old('alamat') }}" maxlength="255">
                @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="rt">RT</label>
                <input class="form-control" id="rt" name="rt" value="{{ old('rt') }}" maxlength="5">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="rw">RW</label>
                <input class="form-control" id="rw" name="rw" value="{{ old('rw') }}" maxlength="5">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="desa">Desa / kelurahan</label>
                <input class="form-control" id="desa" name="desa" value="{{ old('desa') }}" maxlength="100">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="kecamatan">Kecamatan</label>
                <input class="form-control" id="kecamatan" name="kecamatan" value="{{ old('kecamatan') }}" maxlength="100">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="kota">Kota / kabupaten</label>
                <input class="form-control" id="kota" name="kota" value="{{ old('kota') }}" maxlength="100">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="provinsi">Provinsi</label>
                <input class="form-control" id="provinsi" name="provinsi" value="{{ old('provinsi') }}" maxlength="100">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="kode_pos">Kode pos</label>
                <input class="form-control" id="kode_pos" name="kode_pos" value="{{ old('kode_pos') }}" maxlength="10">
            </div>
        </div>

        <div class="alert alert-info">
            Siswa akan berstatus <strong>aktif tanpa rombel</strong>. Penempatan rombel dilakukan melalui menu Rombel.
        </div>

        <div class="emis-actions">
            <a class="btn btn-outline-secondary" href="{{ route('mutasi.index', ['tab' => 'masuk']) }}">Kembali</a>
            <button class="btn btn-madani" type="submit">Simpan mutasi masuk</button>
        </div>
    </form>
</div>

<script>
(() => {
    const jenis = document.getElementById('jenis_sekolah');
    const wrap = document.getElementById('emis-wrap');
    const emis = document.getElementById('nomor_dokumen_emis');
    const sync = () => {
        const madrasah = jenis.value === 'madrasah';
        wrap.style.display = madrasah ? '' : 'none';
        emis.required = madrasah;
        if (!madrasah) emis.value = '';
    };
    jenis.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
