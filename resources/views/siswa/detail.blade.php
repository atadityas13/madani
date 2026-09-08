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

    $identitas = [
        'NIK' => $fmt($siswa->nik),
        'NISN' => $fmt($siswa->nisn),
        'NIS' => $fmt($siswa->nis),
        'NISM' => $fmt($siswa->nism),
        'TEMPAT LAHIR' => $fmt($siswa->tempat_lahir),
        'TANGGAL LAHIR' => $fmt($siswa->tanggal_lahir),
        'JENIS KELAMIN' => $jk,
        'AGAMA' => $fmt($siswa->agama),
        'KEWARGANEGARAAN' => $fmt($siswa->kewarganegaraan),
        'JUMLAH SAUDARA' => $fmt($siswa->jumlah_saudara),
        'ANAK KE' => $fmt($siswa->anak_ke),
        'HOBI' => $fmt($siswa->hobi),
        'CITA-CITA' => $fmt($siswa->cita_cita),
    ];

    $kontakStatus = [
        'EMAIL' => $siswa->tidak_punya_email ? '—' : $fmt($siswa->email),
        'NO HP' => $siswa->tidak_punya_hp ? '—' : $fmt($siswa->no_hp),
        'STATUS KEAKTIFAN' => $fmt(str_replace('_', ' ', (string) $siswa->status_keaktifan)),
        'TANGGAL NONAKTIF' => $fmt($siswa->tanggal_nonaktif),
        'ALASAN NONAKTIF' => $fmt($siswa->alasan_nonaktif),
        'PERNYATAAN' => $siswa->pernyataan
            ? $fmt($siswa->pernyataan->dikonfirmasi_at)
            : 'Belum',
    ];

    $periodikRows = [
        'TEMPAT TINGGAL' => $fmt($periodik?->tempat_tinggal),
        'ALAMAT' => $fmt($periodik?->alamat),
        'BLOK' => $fmt($periodik?->blok),
        'RT' => $fmt($periodik?->rt),
        'RW' => $fmt($periodik?->rw),
        'DESA' => $fmt($periodik?->desa),
        'KECAMATAN' => $fmt($periodik?->kecamatan),
        'KOTA/KABUPATEN' => $fmt($periodik?->kota),
        'PROVINSI' => $fmt($periodik?->provinsi),
        'KODE POS' => $fmt($periodik?->kode_pos),
        'KOORDINAT' => $fmt($periodik?->koordinat),
        'TRANSPORTASI' => $fmt($periodik?->transportasi),
        'JARAK' => $fmt($periodik?->jarak),
        'WAKTU TEMPUH' => $fmt($periodik?->waktu_tempuh),
        'PEMBIAYA' => $fmt($periodik?->pembiaya),
        'NO KK' => $fmt($periodik?->no_kk),
        'KEPALA KELUARGA' => $fmt($periodik?->kepala_keluarga),
        'PENGHASILAN GABUNGAN' => $fmt($periodik?->penghasilan_gabungan),
        'PRA SEKOLAH' => $fmt($periodik?->pra_sekolah),
        'PERNAH TK/RA' => $fmt($periodik?->pernah_tk_ra),
        'PERNAH PAUD' => $fmt($periodik?->pernah_paud),
        'IMUNISASI' => $fmt($periodik?->imunisasi),
        'KEBUTUHAN KHUSUS' => $fmt($periodik?->kebutuhanKhususLabel()),
        'DISABILITAS' => $fmt($periodik?->disabilitasLabel()),
        'TANGGAL MASUK' => $fmt($periodik?->tanggal_masuk),
        'ALASAN MASUK' => $fmt($periodik?->alasan_masuk),
        'NPSN ASAL' => $fmt($periodik?->npsn_asal),
        'NAMA SEKOLAH ASAL' => $fmt($periodik?->nama_sekolah_asal),
    ];

    $bantuan = [
        'NO KIP' => $periodik?->tidak_punya_kip ? '—' : $fmt($periodik?->no_kip),
        'NO KKS' => $periodik?->tidak_punya_kks ? '—' : $fmt($periodik?->no_kks),
        'NO PKH' => $periodik?->tidak_punya_pkh ? '—' : $fmt($periodik?->no_pkh),
    ];

    $ortuSections = [
        'Ayah' => $siswa->ayah,
        'Ibu' => $siswa->ibu,
        'Wali' => $siswa->wali,
    ];

    $barisOrtu = function (?\App\Models\OrangTua $ortu, string $judul) use ($fmt): array {
        $rows = [
            'NAMA' => $fmt($ortu?->nama),
            'NIK' => $fmt($ortu?->nik),
            'STATUS' => $fmt($ortu?->status),
            'STATUS HIDUP' => $fmt($ortu?->status_hidup),
            'TEMPAT LAHIR' => $fmt($ortu?->tempat_lahir),
            'TANGGAL LAHIR' => $fmt($ortu?->tanggal_lahir),
            'PENDIDIKAN' => $fmt($ortu?->pendidikan),
            'PEKERJAAN' => $fmt($ortu?->pekerjaan),
            'PENGHASILAN' => $fmt($ortu?->penghasilan),
            'NO HP' => $ortu?->tidak_punya_hp ? '—' : $fmt($ortu?->no_hp),
        ];

        if ($judul === 'Wali') {
            $rows['HUBUNGAN'] = $fmt($ortu?->hubungan);
        }

        return array_merge($rows, [
            'STATUS TEMPAT TINGGAL' => $fmt($ortu?->status_tempat_tinggal),
            'ALAMAT' => $fmt($ortu?->alamat),
            'BLOK' => $fmt($ortu?->blok),
            'RT' => $fmt($ortu?->rt),
            'RW' => $fmt($ortu?->rw),
            'DESA' => $fmt($ortu?->desa),
            'KECAMATAN' => $fmt($ortu?->kecamatan),
            'KOTA/KABUPATEN' => $fmt($ortu?->kota),
            'PROVINSI' => $fmt($ortu?->provinsi),
            'KODE POS' => $fmt($ortu?->kode_pos),
        ]);
    };
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

            <p
                class="siswa-detail__rombel{{ $rombelLabel !== '—' ? ' is-copyable' : '' }}"
                @if ($rombelLabel !== '—')
                    role="button"
                    tabindex="0"
                    data-copy="{{ $rombelLabel }}"
                    title="Klik untuk menyalin"
                @endif
            >{{ $rombelLabel }}</p>

            <h3 class="siswa-detail__section">Identitas</h3>
            @include('siswa.partials.detail-rows', ['rows' => $identitas])

            <h3 class="siswa-detail__section">Kontak &amp; status</h3>
            @include('siswa.partials.detail-rows', ['rows' => $kontakStatus])
        </div>
    </div>
</div>

<div class="madani-card siswa-detail mb-3" data-siswa-detail>
    <h3 class="siswa-detail__section mt-0">Tempat tinggal &amp; data periodik</h3>
    @include('siswa.partials.detail-rows', ['rows' => $periodikRows])

    <h3 class="siswa-detail__section">Bantuan</h3>
    @include('siswa.partials.detail-rows', ['rows' => $bantuan])
</div>

@foreach ($ortuSections as $judul => $ortu)
    <div class="madani-card siswa-detail mb-3" data-siswa-detail>
        <h3 class="siswa-detail__section mt-0">{{ $judul }}</h3>
        @include('siswa.partials.detail-rows', ['rows' => $barisOrtu($ortu, $judul)])
    </div>
@endforeach

<div class="madani-card siswa-detail mb-3" data-siswa-detail>
    <h3 class="siswa-detail__section mt-0">Rekam didik</h3>
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
    <h3 class="siswa-detail__section mt-0">Prestasi</h3>
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
    <h3 class="siswa-detail__section mt-0">Beasiswa / bantuan</h3>
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
        <p class="text-secondary mb-0">Belum ada data beasiswa.</p>
    @endforelse
</div>
@endsection
