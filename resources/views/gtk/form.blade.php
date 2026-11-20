@extends('layouts.app')

@php
    $baru = ! $gtk->exists;
    $referensi = $referensi ?? ['siap' => false, 'mapels' => [], 'rumpuns' => [], 'peringatan' => null];
@endphp

@section('title', $baru ? 'Tambah GTK' : 'Ubah GTK')
@section('heading', $baru ? 'Tambah GTK' : 'Ubah GTK')
@section('subheading', 'Guru dan tenaga kependidikan')

@section('content')
<div class="madani-card p-4" style="max-width: 840px;">
    <form method="POST" action="{{ $baru ? route('gtk.store') : route('gtk.update', $gtk) }}">
        @csrf
        @unless ($baru)
            @method('PUT')
        @endunless

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Jenis</label>
                <x-emis-select name="jenis" :options="\App\Models\Gtk::jenisOptions()" :value="old('jenis', $gtk->jenis ?: 'guru')" required />
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <x-emis-select name="status" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']" :value="old('status', $gtk->status)" required />
            </div>
            <div class="col-md-4">
                <label class="form-label">Jenis kelamin</label>
                <x-emis-select name="jenis_kelamin" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']" :value="old('jenis_kelamin', $gtk->jenis_kelamin)" />
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">Gelar depan</label>
                <input class="form-control" name="gelar_depan" value="{{ old('gelar_depan', $gtk->gelar_depan) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Nama</label>
                <input class="form-control @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama', $gtk->nama) }}" required>
                @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Gelar belakang</label>
                <input class="form-control" name="gelar_belakang" value="{{ old('gelar_belakang', $gtk->gelar_belakang) }}">
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">NIP / NIK</label>
                <input class="form-control" name="nip" value="{{ old('nip', $gtk->nip) }}">
                <div class="form-text">Dipakai sebagai username akun Ta'lim.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">NUPTK</label>
                <input class="form-control @error('nuptk') is-invalid @enderror" name="nuptk" value="{{ old('nuptk', $gtk->nuptk) }}">
                @error('nuptk') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">NRG</label>
                <input class="form-control @error('nrg') is-invalid @enderror" name="nrg" value="{{ old('nrg', $gtk->nrg) }}">
                @error('nrg') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Tempat lahir</label>
                <input class="form-control" name="tempat_lahir" value="{{ old('tempat_lahir', $gtk->tempat_lahir) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Tanggal lahir</label>
                <input class="form-control" type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', optional($gtk->tanggal_lahir)->format('Y-m-d')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Agama</label>
                <input class="form-control" name="agama" value="{{ old('agama', $gtk->agama) }}">
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Nomor HP</label>
                <input class="form-control" name="nomor_hp" value="{{ old('nomor_hp', $gtk->nomor_hp) }}">
            </div>
            <div class="col-md-8">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="email" value="{{ old('email', $gtk->email) }}">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Alamat</label>
            <textarea class="form-control" name="alamat" rows="2">{{ old('alamat', $gtk->alamat) }}</textarea>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Jabatan</label>
                <input class="form-control" name="jabatan" value="{{ old('jabatan', $gtk->jabatan) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Golongan</label>
                <input class="form-control" name="golongan" value="{{ old('golongan', $gtk->golongan) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status pegawai</label>
                <input class="form-control" name="status_pegawai" value="{{ old('status_pegawai', $gtk->status_pegawai) }}" placeholder="PNS / PPPK / NON_ASN">
            </div>
        </div>

        @php
            $pilihanIjazah = old('ijazah_selection');
            if ($pilihanIjazah === null) {
                if ($gtk->mapel_ijazah) {
                    $pilihanIjazah = 'mapel:'.$gtk->mapel_ijazah;
                } elseif ($gtk->rumpun_ijazah) {
                    $pilihanIjazah = 'rumpun:'.$gtk->rumpun_ijazah;
                } else {
                    $pilihanIjazah = '';
                }
            }
            $opsiIjazah = [];
            foreach ($referensi['rumpuns'] as $nama) {
                $opsiIjazah['rumpun:'.$nama] = $nama;
            }
            foreach ($referensi['mapels'] as $nama) {
                $opsiIjazah['mapel:'.$nama] = $nama;
            }
            $opsiMapel = [];
            foreach ($referensi['mapels'] as $nama) {
                $opsiMapel[$nama] = $nama;
            }
            $sudahSertifikasi = (string) old('status_sertifikasi', $gtk->status_sertifikasi ? '1' : '0') === '1';
        @endphp

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Kode guru (KG)</label>
                <input class="form-control text-uppercase @error('kode_internal') is-invalid @enderror" name="kode_internal" value="{{ old('kode_internal', $gtk->kode_internal) }}" maxlength="2" placeholder="AA">
                <div class="form-text">2 karakter. Ditiru ke Simpatisans saat sync.</div>
                @error('kode_internal') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">DUK</label>
                <input class="form-control" name="duk" value="{{ old('duk', $gtk->duk) }}">
            </div>
        </div>

        @if ($referensi['peringatan'])
            <div class="alert alert-warning">{{ $referensi['peringatan'] }}</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Mapel ijazah (S1)</label>
                @if ($referensi['siap'])
                    <x-emis-select name="ijazah_selection" :options="$opsiIjazah" :value="$pilihanIjazah" placeholder="Pilih rumpun atau mapel" />
                    <div class="form-text">Rumpun ditampilkan lebih dulu, lalu mapel, sama seperti Simpatisans.</div>
                @else
                    <p class="form-control-plaintext mb-0">{{ $gtk->mapel_ijazah ?: ($gtk->rumpun_ijazah ?: 'Belum diisi') }}</p>
                    <input type="hidden" name="mapel_ijazah" value="{{ old('mapel_ijazah', $gtk->mapel_ijazah) }}">
                    <input type="hidden" name="rumpun_ijazah" value="{{ old('rumpun_ijazah', $gtk->rumpun_ijazah) }}">
                @endif
            </div>
            <div class="col-md-6">
                <span class="form-label d-block">Tersertifikasi (TPG)</span>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status_sertifikasi" id="sertifikasi_ya" value="1" @checked($sudahSertifikasi)>
                        <label class="form-check-label" for="sertifikasi_ya">Ya</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status_sertifikasi" id="sertifikasi_tidak" value="0" @checked(! $sudahSertifikasi)>
                        <label class="form-check-label" for="sertifikasi_tidak">Tidak</label>
                    </div>
                </div>
                <div id="mapel-sertifikasi-wrap" class="mt-3" @if (! $sudahSertifikasi) style="display: none;" @endif>
                    <label class="form-label">Mapel sertifikasi</label>
                    @if ($referensi['siap'])
                        <x-emis-select name="mapel_sertifikasi" :options="$opsiMapel" :value="old('mapel_sertifikasi', $gtk->mapel_sertifikasi)" placeholder="Pilih mapel sertifikasi" />
                    @else
                        <p class="form-control-plaintext mb-0">{{ $gtk->mapel_sertifikasi ?: 'Belum diisi' }}</p>
                        <input type="hidden" name="mapel_sertifikasi" value="{{ old('mapel_sertifikasi', $gtk->mapel_sertifikasi) }}">
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="form-check">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="is_bk"
                        id="is_bk"
                        value="1"
                        @checked(old('is_bk', $gtk->is_bk))
                    >
                    <label class="form-check-label" for="is_bk">Guru Bimbingan dan Konseling (BK)</label>
                </div>
            </div>
        </div>

        <script>
            document.querySelectorAll('input[name="status_sertifikasi"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    document.getElementById('mapel-sertifikasi-wrap').style.display = radio.value === '1' && radio.checked ? '' : 'none';
                });
            });
        </script>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('gtk.index') }}">Batal</a>
            <button class="btn btn-madani" type="submit">Simpan</button>
        </div>
    </form>
</div>
@endsection
