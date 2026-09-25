@extends('layouts.app')

@section('title', 'Mutasi keluar')
@section('heading', 'Mutasi keluar')
@section('subheading', 'Nonaktifkan siswa dan catat sekolah tujuan')

@section('content')
<div class="madani-card p-4">
    <form method="POST" action="{{ route('mutasi.keluar.store') }}" id="form-mutasi-keluar">
        @csrf

        <div class="stat-label mb-3">Pilih siswa</div>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label" for="tingkat">Tingkat</label>
                <select class="form-select" id="tingkat" name="tingkat_filter">
                    <option value="">Pilih tingkat</option>
                    @foreach ($tingkatOptions as $option)
                        <option value="{{ $option }}" @selected($tingkat === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="siswa_id">Siswa</label>
                <select class="form-select @error('siswa_id') is-invalid @enderror" id="siswa_id" name="siswa_id" required @disabled($tingkat === '')>
                    <option value="">{{ $tingkat === '' ? 'Pilih tingkat dulu' : 'Pilih siswa' }}</option>
                    @foreach ($siswas as $siswa)
                        <option
                            value="{{ $siswa->id }}"
                            data-nisn="{{ $siswa->nisn }}"
                            data-rombel="{{ $siswa->rombels->first()?->label() ?? '—' }}"
                            data-wali="{{ $siswa->wali?->nama ?: '—' }}"
                            @selected((string) $siswaId === (string) $siswa->id)
                        >{{ $siswa->nama }}{{ $siswa->nisn ? ' · '.$siswa->nisn : '' }}</option>
                    @endforeach
                </select>
                @error('siswa_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="cari">Cari nama / NISN</label>
                <input class="form-control" type="search" id="cari" placeholder="Filter daftar…" @disabled($tingkat === '')>
            </div>
        </div>

        <div class="row g-3 mb-4" id="siswa-detail" @style(['display: none' => blank($siswaId)])>
            <div class="col-md-4">
                <label class="form-label">NISN</label>
                <input class="form-control bg-light" id="detail-nisn" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Rombel</label>
                <input class="form-control bg-light" id="detail-rombel" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nama wali</label>
                <input class="form-control bg-light" id="detail-wali" readonly>
            </div>
        </div>

        <div class="stat-label mb-3">Sekolah tujuan</div>
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
                <label class="form-label" for="nama_sekolah">Nama sekolah tujuan</label>
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

        <div class="emis-actions">
            <a class="btn btn-outline-secondary" href="{{ route('mutasi.index', ['tab' => 'keluar']) }}">Kembali</a>
            <button class="btn btn-madani" type="submit">Simpan mutasi keluar</button>
        </div>
    </form>
</div>

<script>
(() => {
    const tingkat = document.getElementById('tingkat');
    const siswaSelect = document.getElementById('siswa_id');
    const cari = document.getElementById('cari');
    const detail = document.getElementById('siswa-detail');
    const jenis = document.getElementById('jenis_sekolah');
    const wrap = document.getElementById('emis-wrap');
    const emis = document.getElementById('nomor_dokumen_emis');
    const createUrl = @json(route('mutasi.keluar.create'));

    tingkat.addEventListener('change', () => {
        const params = new URLSearchParams();
        if (tingkat.value) params.set('tingkat', tingkat.value);
        window.location = createUrl + (params.toString() ? '?' + params.toString() : '');
    });

    const syncDetail = () => {
        const opt = siswaSelect.selectedOptions[0];
        if (!opt || !opt.value) {
            detail.style.display = 'none';
            return;
        }
        document.getElementById('detail-nisn').value = opt.dataset.nisn || '—';
        document.getElementById('detail-rombel').value = opt.dataset.rombel || '—';
        document.getElementById('detail-wali').value = opt.dataset.wali || '—';
        detail.style.display = '';
    };
    siswaSelect.addEventListener('change', syncDetail);
    syncDetail();

    cari.addEventListener('input', () => {
        const q = cari.value.trim().toLowerCase();
        Array.from(siswaSelect.options).forEach((opt, idx) => {
            if (idx === 0) return;
            const text = (opt.textContent || '').toLowerCase();
            opt.hidden = q !== '' && !text.includes(q);
        });
    });

    const syncEmis = () => {
        const madrasah = jenis.value === 'madrasah';
        wrap.style.display = madrasah ? '' : 'none';
        emis.required = madrasah;
        if (!madrasah) emis.value = '';
    };
    jenis.addEventListener('change', syncEmis);
    syncEmis();
})();
</script>
@endsection
