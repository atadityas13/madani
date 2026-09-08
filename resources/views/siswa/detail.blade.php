@extends('layouts.app')

@section('title', 'Detail siswa')
@section('heading', 'Detail Siswa')
@section('subheading', 'MTsN 11 Majalengka')

@section('content')
@php
    $inisial = collect(preg_split('/\s+/', trim($siswa->nama)))
        ->filter()
        ->take(2)
        ->map(fn ($p) => strtoupper(substr($p, 0, 1)))
        ->implode('') ?: 'SW';

    $fmt = function (mixed $value): string {
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d');
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        if (is_array($value)) {
            $items = array_values(array_filter($value, fn ($item) => filled($item)));

            return $items === [] ? '—' : implode(', ', $items);
        }

        if ($value === null || $value === '') {
            return '—';
        }

        return (string) $value;
    };

    $jk = match ($siswa->jenis_kelamin) {
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
        default => '—',
    };

    $rombelLabel = $rombel ? $rombel->label() : '—';
    $statusKeaktifan = $fmt(str_replace('_', ' ', (string) $siswa->status_keaktifan));
    $nonaktif = $siswa->status_keaktifan === 'nonaktif';

    $praSekolah = [];
    if ($periodik?->pernah_tk_ra) {
        $praSekolah[] = 'Pernah TK/RA';
    }
    if ($periodik?->pernah_paud) {
        $praSekolah[] = 'Pernah PAUD';
    }

    $kebutuhanKhusus = $periodik?->kebutuhanKhususLabel();
    if ($kebutuhanKhusus === 'Lainnya') {
        $kebutuhanKhusus = $periodik?->kebutuhan_khusus_lainnya;
    }

    $disabilitasItems = collect($periodik?->disabilitas ?? [])
        ->filter()
        ->map(fn ($item) => $item === 'Lainnya'
            ? ($periodik?->disabilitas_lainnya ?: 'Lainnya')
            : $item)
        ->values()
        ->all();

    $identitas = [
        'NIK' => $fmt($siswa->nik),
        'NISN' => $fmt($siswa->nisn),
        'NISM' => $fmt($siswa->nism ?: $siswa->nis),
        'TEMPAT LAHIR' => $fmt($siswa->tempat_lahir),
        'TANGGAL LAHIR' => $fmt($siswa->tanggal_lahir),
        'JENIS KELAMIN' => $jk,
        'JUMLAH SAUDARA' => $fmt($siswa->jumlah_saudara),
        'ANAK KE' => $fmt($siswa->anak_ke),
        'AGAMA' => $fmt($siswa->agama),
        'CITA-CITA' => $fmt($siswa->cita_cita),
        'HOBI' => $fmt($siswa->hobi),
        'NOMOR HP' => $siswa->tidak_punya_hp ? '—' : $fmt($siswa->no_hp),
        'ALAMAT EMAIL' => $siswa->tidak_punya_email ? '—' : $fmt($siswa->email),
        'YANG MEMBIAYAI SEKOLAH' => $fmt($periodik?->pembiaya),
        'PRA SEKOLAH' => $praSekolah === []
            ? ($periodik ? 'Tidak' : '—')
            : implode(', ', $praSekolah),
        'IMUNISASI' => $fmt($periodik?->imunisasi),
        'NOMOR KIP' => $periodik?->tidak_punya_kip ? '—' : $fmt($periodik?->no_kip),
        'NO KK' => $fmt($periodik?->no_kk),
        'NAMA KEPALA KELUARGA' => $fmt($periodik?->kepala_keluarga),
    ];

    $barisAyahIbu = function (?\App\Models\OrangTua $ortu) use ($fmt): array {
        return [
            'NAMA LENGKAP' => $fmt($ortu?->nama),
            'STATUS' => $fmt($ortu?->status_hidup),
            'NIK' => $fmt($ortu?->nik),
            'TEMPAT LAHIR' => $fmt($ortu?->tempat_lahir),
            'TANGGAL LAHIR' => $fmt($ortu?->tanggal_lahir),
            'PENDIDIKAN TERAKHIR' => $fmt($ortu?->pendidikan),
            'PEKERJAAN' => $fmt($ortu?->pekerjaan),
            'NOMOR HP' => $ortu?->tidak_punya_hp ? '—' : $fmt($ortu?->no_hp),
        ];
    };

    $barisWali = function (?\App\Models\OrangTua $ortu) use ($fmt): array {
        return [
            'STATUS' => $fmt($ortu?->status),
            'HUBUNGAN' => $fmt($ortu?->hubungan),
            'NAMA LENGKAP' => $fmt($ortu?->nama),
            'NIK' => $fmt($ortu?->nik),
            'TEMPAT LAHIR' => $fmt($ortu?->tempat_lahir),
            'TANGGAL LAHIR' => $fmt($ortu?->tanggal_lahir),
            'PENDIDIKAN TERAKHIR' => $fmt($ortu?->pendidikan),
            'PEKERJAAN' => $fmt($ortu?->pekerjaan),
            'NOMOR HP' => $ortu?->tidak_punya_hp ? '—' : $fmt($ortu?->no_hp),
        ];
    };

    $barisAlamatOrtu = function (?\App\Models\OrangTua $ortu) use ($fmt): array {
        return [
            'STATUS TEMPAT TINGGAL' => $fmt($ortu?->status_tempat_tinggal),
            'PROVINSI' => $fmt($ortu?->provinsi),
            'KABUPATEN' => $fmt($ortu?->kota),
            'KECAMATAN' => $fmt($ortu?->kecamatan),
            'DESA' => $fmt($ortu?->desa),
            'RT' => $fmt($ortu?->rt),
            'RW' => $fmt($ortu?->rw),
            'ALAMAT LENGKAP' => $fmt($ortu?->alamat),
            'KODE POS' => $fmt($ortu?->kode_pos),
        ];
    };

    $alamatSiswa = [
        'STATUS TEMPAT TINGGAL' => $fmt($periodik?->tempat_tinggal),
        'PROVINSI' => $fmt($periodik?->provinsi),
        'KABUPATEN' => $fmt($periodik?->kota),
        'KECAMATAN' => $fmt($periodik?->kecamatan),
        'DESA' => $fmt($periodik?->desa),
        'RT' => $fmt($periodik?->rt),
        'RW' => $fmt($periodik?->rw),
        'ALAMAT LENGKAP' => $fmt($periodik?->alamat),
        'KODE POS' => $fmt($periodik?->kode_pos),
        'KOORDINAT' => $fmt($periodik?->koordinat),
        'JARAK TEMPAT TINGGAL KE MADRASAH' => $fmt($periodik?->jarak),
        'TRANSPORTASI KE SEKOLAH' => $fmt($periodik?->transportasi),
        'WAKTU TEMPUH' => $fmt($periodik?->waktu_tempuh),
    ];

    $penghasilan = [
        'PENGHASILAN GABUNGAN RATA-RATA' => $fmt($periodik?->penghasilan_gabungan),
        'NOMOR KKS' => $periodik?->tidak_punya_kks ? '—' : $fmt($periodik?->no_kks),
        'NOMOR PKH' => $periodik?->tidak_punya_pkh ? '—' : $fmt($periodik?->no_pkh),
    ];
@endphp

<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('siswa.index') }}">Kembali</a>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('siswa.portofolio', $siswa) }}">Portofolio</a>
        @can('update', $siswa)
            <a class="btn btn-madani btn-sm" href="{{ route('siswa.edit', $siswa) }}">Edit data</a>
        @endcan
    </div>
</div>

<div class="madani-card siswa-detail mb-3" data-siswa-detail>
    <div class="siswa-detail__grid">
        <aside class="siswa-detail__photo">
            @if ($fotoUrl)
                <img src="{{ $fotoUrl }}" alt="Foto {{ $siswa->nama }}">
            @else
                <div class="siswa-detail__photo-fallback" aria-hidden="true">{{ $inisial }}</div>
            @endif
        </aside>

        <div class="siswa-detail__body">
            <h2
                class="siswa-detail__name is-copyable"
                role="button"
                tabindex="0"
                data-copy="{{ $siswa->nama }}"
                title="Klik untuk menyalin"
            >{{ $siswa->nama }}</h2>

            <p class="siswa-detail__meta">
                <span
                    class="siswa-detail__meta-item{{ $rombelLabel !== '—' ? ' is-copyable' : '' }}"
                    @if ($rombelLabel !== '—')
                        role="button" tabindex="0" data-copy="{{ $rombelLabel }}" title="Klik untuk menyalin"
                    @endif
                >{{ $rombelLabel }}</span>
                <span class="siswa-detail__meta-sep" aria-hidden="true">|</span>
                <span
                    class="siswa-detail__meta-item is-copyable"
                    role="button"
                    tabindex="0"
                    data-copy="{{ $statusKeaktifan }}"
                    title="Klik untuk menyalin"
                >{{ $statusKeaktifan }}</span>
                @if ($nonaktif)
                    <span class="siswa-detail__meta-sep" aria-hidden="true">|</span>
                    <span
                        class="siswa-detail__meta-item{{ $fmt($siswa->alasan_nonaktif) !== '—' ? ' is-copyable' : '' }}"
                        @if ($fmt($siswa->alasan_nonaktif) !== '—')
                            role="button" tabindex="0" data-copy="{{ $fmt($siswa->alasan_nonaktif) }}" title="Klik untuk menyalin"
                        @endif
                    >{{ $fmt($siswa->alasan_nonaktif) }}</span>
                    <span class="siswa-detail__meta-sep" aria-hidden="true">|</span>
                    <span
                        class="siswa-detail__meta-item{{ $fmt($siswa->tanggal_nonaktif) !== '—' ? ' is-copyable' : '' }}"
                        @if ($fmt($siswa->tanggal_nonaktif) !== '—')
                            role="button" tabindex="0" data-copy="{{ $fmt($siswa->tanggal_nonaktif) }}" title="Klik untuk menyalin"
                        @endif
                    >{{ $fmt($siswa->tanggal_nonaktif) }}</span>
                @endif
            </p>

            <h3 class="siswa-detail__section">1. Identitas</h3>
            @include('siswa.partials.detail-rows', ['rows' => $identitas])

            <div class="siswa-detail__dokumen-grid">
                @include('siswa.partials.detail-dokumen', [
                    'judul' => 'Kartu keluarga',
                    'jenis' => 'kk',
                    'dokumen' => $siswa->dokumenJenis('kk'),
                    'siswa' => $siswa,
                ])
                @include('siswa.partials.detail-dokumen', [
                    'judul' => 'KIP',
                    'jenis' => 'kip',
                    'dokumen' => $siswa->dokumenJenis('kip'),
                    'siswa' => $siswa,
                ])
            </div>
        </div>
    </div>
</div>

<div class="madani-card siswa-detail mb-3" data-siswa-detail>
    <h3 class="siswa-detail__section mt-0">Bagian Orang Tua</h3>

    <h4 class="siswa-detail__subsection">Ayah Kandung</h4>
    @include('siswa.partials.detail-rows', ['rows' => $barisAyahIbu($siswa->ayah)])

    <h4 class="siswa-detail__subsection">Ibu Kandung</h4>
    @include('siswa.partials.detail-rows', ['rows' => $barisAyahIbu($siswa->ibu)])

    <h4 class="siswa-detail__subsection">Wali</h4>
    @include('siswa.partials.detail-rows', ['rows' => $barisWali($siswa->wali)])
</div>

<div class="madani-card siswa-detail mb-3" data-siswa-detail>
    <h3 class="siswa-detail__section mt-0">Bagian Penghasilan Orang tua/Wali</h3>
    @include('siswa.partials.detail-rows', ['rows' => $penghasilan])
    <div class="siswa-detail__dokumen-grid">
        @include('siswa.partials.detail-dokumen', [
            'judul' => 'KKS',
            'jenis' => 'kks',
            'dokumen' => $siswa->dokumenJenis('kks'),
            'siswa' => $siswa,
        ])
        @include('siswa.partials.detail-dokumen', [
            'judul' => 'PKH',
            'jenis' => 'pkh',
            'dokumen' => $siswa->dokumenJenis('pkh'),
            'siswa' => $siswa,
        ])
    </div>
</div>

<div class="madani-card siswa-detail mb-3" data-siswa-detail>
    <h3 class="siswa-detail__section mt-0">Bagian Alamat</h3>

    <h4 class="siswa-detail__subsection">Ayah Kandung</h4>
    @include('siswa.partials.detail-rows', ['rows' => $barisAlamatOrtu($siswa->ayah)])

    <h4 class="siswa-detail__subsection">Ibu Kandung</h4>
    @include('siswa.partials.detail-rows', ['rows' => $barisAlamatOrtu($siswa->ibu)])

    <h4 class="siswa-detail__subsection">Wali</h4>
    @include('siswa.partials.detail-rows', ['rows' => $barisAlamatOrtu($siswa->wali)])

    <h4 class="siswa-detail__subsection">Siswa</h4>
    @include('siswa.partials.detail-rows', ['rows' => $alamatSiswa])
</div>

<div class="madani-card siswa-detail mb-3" data-siswa-detail>
    <h3 class="siswa-detail__section mt-0">Bagian Rekam Didik</h3>
    @include('siswa.partials.detail-rows', ['rows' => [
        'NAMA IJAZAH' => $fmt($siswa->rekamDidik?->nama_ijazah),
        'TEMPAT LAHIR IJAZAH' => $fmt($siswa->rekamDidik?->tempat_lahir_ijazah),
        'TANGGAL LAHIR IJAZAH' => $fmt($siswa->rekamDidik?->tanggal_lahir_ijazah),
        'JENIS KELAMIN IJAZAH' => $fmt($siswa->rekamDidik?->jenis_kelamin_ijazah),
        'NAMA AYAH IJAZAH' => $fmt($siswa->rekamDidik?->nama_ayah_ijazah),
        'NAMA SD' => $fmt($siswa->rekamDidik?->nama_sd),
        'NPSN' => $fmt($siswa->rekamDidik?->npsn),
        'TAHUN AJARAN KELULUSAN' => $fmt($siswa->rekamDidik?->tahun_ajaran_kelulusan),
        'NIP KEPALA SEKOLAH' => $fmt($siswa->rekamDidik?->nip_kepala_sekolah),
        'NAMA KEPALA SEKOLAH' => $fmt($siswa->rekamDidik?->nama_kepala_sekolah),
        'NOMOR SERI IJAZAH' => $fmt($siswa->rekamDidik?->nomor_seri_ijazah),
        'TANGGAL TERBIT IJAZAH' => $fmt($siswa->rekamDidik?->tanggal_terbit_ijazah),
        'STATUS VERVAL' => $fmt($siswa->rekamDidik?->status_verval),
        'IJAZAH SESUAI' => $fmt($siswa->rekamDidik?->ijazah_sesuai),
    ]])
</div>

<div class="madani-card siswa-detail mb-3" data-siswa-detail>
    <h3 class="siswa-detail__section mt-0">Bagian Kebutuhan Khusus &amp; Disabilitas</h3>
    @include('siswa.partials.detail-rows', ['rows' => [
        'KEBUTUHAN KHUSUS' => $fmt($kebutuhanKhusus),
        'DISABILITAS' => $fmt($disabilitasItems),
    ]])
</div>

<div class="madani-card siswa-detail mb-3" data-siswa-detail>
    <h3 class="siswa-detail__section mt-0">Bagian Prestasi</h3>
    @forelse ($siswa->prestasis as $index => $prestasi)
        <h4 class="siswa-detail__subsection">Prestasi {{ $index + 1 }}</h4>
        @include('siswa.partials.detail-rows', ['rows' => [
            'NAMA' => $fmt($prestasi->nama),
            'JENIS' => $fmt($prestasi->jenis),
            'TINGKAT' => $fmt($prestasi->tingkat),
            'TAHUN' => $fmt($prestasi->tahun),
            'PENYELENGGARA' => $fmt($prestasi->penyelenggara),
        ]])
    @empty
        <p class="text-secondary mb-0">Belum ada data prestasi.</p>
    @endforelse
</div>

<div class="madani-card siswa-detail mb-3" data-siswa-detail>
    <h3 class="siswa-detail__section mt-0">Bagian Bantuan pendidikan</h3>
    @forelse ($siswa->beasiswas as $index => $beasiswa)
        <h4 class="siswa-detail__subsection">Item {{ $index + 1 }}</h4>
        @include('siswa.partials.detail-rows', ['rows' => [
            'TAHUN' => $fmt($beasiswa->tahun),
            'KATEGORI' => $fmt($beasiswa->kategori),
            'NAMA' => $fmt($beasiswa->nama),
            'INSTANSI' => $fmt($beasiswa->instansi),
            'JENIS INSTANSI' => $fmt($beasiswa->jenis_instansi),
            'JANGKA (BULAN)' => $fmt($beasiswa->jangka_bulan),
            'NOMINAL' => $fmt($beasiswa->nominal),
            'NOMOR REKENING' => $fmt($beasiswa->nomor_rekening),
        ]])
    @empty
        <p class="text-secondary mb-0">Belum ada data bantuan pendidikan.</p>
    @endforelse
</div>
@endsection
