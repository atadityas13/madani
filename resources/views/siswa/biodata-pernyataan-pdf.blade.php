<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Biodata dan Surat Pernyataan — {{ $siswa->nama }}</title>
    <style>
        @page { margin: 36px 64px 44px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.4;
        }
        .kop { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .kop td { vertical-align: middle; }
        .kop-logo { width: 84px; }
        .kop-logo img { width: 78px; height: auto; }
        .kop-text { text-align: center; padding: 0 8px; }
        .kop-text .line1 { font-size: 13px; font-weight: bold; letter-spacing: 0.2px; }
        .kop-text .line2 { font-size: 12px; font-weight: bold; }
        .kop-text .line3 { font-size: 12px; font-weight: bold; margin-top: 1px; }
        .kop-text .line4 { font-size: 9.5px; margin-top: 2px; white-space: nowrap; }
        .kop-text .line5 { font-size: 9.5px; margin-top: 1px; white-space: nowrap; }
        .kop-qr { width: 82px; }
        .kop-line {
            border: 0;
            border-top: 3px solid #111;
            border-bottom: 1px solid #111;
            margin: 6px 0 14px;
        }
        .doc-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin: 0 0 12px;
        }
        .head-data { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        .head-data td { vertical-align: top; }
        .foto-box { width: 96px; text-align: right; }
        .foto-box img {
            width: 86px;
            height: 108px;
            object-fit: cover;
            border: 1px solid #999;
        }
        .foto-placeholder {
            width: 86px;
            height: 108px;
            border-collapse: collapse;
            border: 1px solid #999;
        }
        .foto-placeholder td {
            width: 86px;
            height: 108px;
            text-align: center;
            vertical-align: middle;
            color: #888;
            font-size: 10px;
        }
        .section { font-size: 12px; font-weight: bold; margin: 8px 0 3px; text-transform: uppercase; }
        .section-first { margin-top: 0; margin-bottom: 3px; }
        .page-break { page-break-before: always; }
        .page-break .section:first-child { margin-top: 0; }
        .rows { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .rows td { padding: 2px 0; vertical-align: top; font-size: 11px; }
        .rows .label { width: 168px; }
        .rows .colon { width: 12px; }
        .rows .value { width: auto; }
        .pernyataan-box {
            margin-top: 0;
            text-align: justify;
            font-size: 11px;
            line-height: 1.45;
        }
        .pernyataan-box p { margin: 0 0 6px; }
        .pernyataan-box .penutup { margin: 0; }
        .surat-wrap {
            padding: 28px 18px 0;
        }
        .surat-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin: 0 0 14px;
            letter-spacing: 0.5px;
        }
        .surat-body { text-align: justify; font-size: 11px; line-height: 1.5; }
        .surat-body p { margin: 0 0 6px; }
        .surat-body ol { margin: 6px 0 6px 18px; padding: 0; }
        .surat-body li { margin-bottom: 3px; }
        .identitas-surat { width: 100%; border-collapse: collapse; margin: 8px 0 10px; }
        .identitas-surat td { padding: 1.5px 0; vertical-align: top; }
        .identitas-surat .label { width: 168px; }
        .identitas-surat .colon { width: 12px; }
        .ttd-table {
            width: 100%;
            margin: 34px 0 0;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .ttd-side {
            width: 50%;
            vertical-align: top;
            font-size: 11px;
            padding: 0;
        }
        .ttd-side-left { text-align: left; }
        .ttd-side-right { text-align: right; }
        .ttd-block {
            width: 168px;
            text-align: left;
            display: inline-block;
        }
        .ttd-meta {
            line-height: 1.1;
            margin: 0;
            padding: 0;
        }
        .ttd-img {
            width: 150px;
            height: 72px;
            object-fit: contain;
            object-position: left center;
            margin: 10px 0 4px;
            display: block;
        }
        .ttd-spacer {
            width: 150px;
            height: 72px;
            margin: 10px 0 4px;
        }
        .ttd-name {
            line-height: 1.15;
            margin: 0;
            padding: 0;
        }
        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -18px;
            font-size: 9px;
            color: #444;
            text-align: center;
            border-top: 1px solid #bbb;
            padding-top: 4px;
        }
    </style>
</head>
<body>
@php
    $dash = fn ($v) => filled($v) ? $v : '-';
    $namaMadrasah = strtoupper($madrasah->nama ?: 'MADRASAH TSANAWIYAH NEGERI 11 MAJALENGKA');
    $kontakKop = collect([
        $madrasah->telepon ? 'Telp. '.$madrasah->telepon : null,
        $madrasah->email ? 'E-mail: '.$madrasah->email : null,
    ])->filter()->implode(' ');
    $jk = match ($siswa->jenis_kelamin) {
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
        default => $siswa->jenis_kelamin,
    };
    $ttl = collect([
        $siswa->tempat_lahir,
        $siswa->tanggal_lahir?->locale('id')->translatedFormat('d F Y'),
    ])->filter()->implode(', ');
@endphp

{{-- ========== BIODATA SISWA ========== --}}
<table class="kop">
    <tr>
        <td class="kop-logo">
            @if ($logoDataUri)
                <img src="{{ $logoDataUri }}" alt="Logo">
            @endif
        </td>
        <td class="kop-text">
            <div class="line1">KEMENTERIAN AGAMA REPUBLIK INDONESIA</div>
            <div class="line2">KANTOR KEMENTERIAN AGAMA KABUPATEN MAJALENGKA</div>
            <div class="line3">{{ $namaMadrasah }}</div>
            @if ($kopAlamat !== '')
                <div class="line4">{{ $kopAlamat }}</div>
            @endif
            @if ($kontakKop !== '')
                <div class="line5">{{ $kontakKop }}</div>
            @endif
        </td>
        <td class="kop-qr"></td>
    </tr>
</table>
<hr class="kop-line">

<div class="doc-title">Biodata Siswa</div>

<table class="head-data">
    <tr>
        <td>
            <div class="section section-first">Data siswa</div>
            <table class="rows">
                <tr>
                    <td class="label">NAMA</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $dash($siswa->nama) }}</td>
                </tr>
                @foreach ($identitasRows as [$label, $value])
                    <tr>
                        <td class="label">{{ strtoupper($label) }}</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $dash($value) }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
        <td class="foto-box">
            @if ($fotoDataUri)
                <img src="{{ $fotoDataUri }}" alt="Foto siswa">
            @else
                <table class="foto-placeholder"><tr><td>Foto</td></tr></table>
            @endif
        </td>
    </tr>
</table>

<div class="section">Alamat siswa</div>
<table class="rows">
    @foreach ($alamatRows as [$label, $value])
        <tr>
            <td class="label">{{ strtoupper($label) }}</td>
            <td class="colon">:</td>
            <td class="value">{{ $dash($value) }}</td>
        </tr>
    @endforeach
</table>

<div class="page-break">
@foreach ([['Ayah kandung', $ayahRows], ['Ibu kandung', $ibuRows], ['Wali', $waliRows]] as [$judulOrtu, $rowsOrtu])
    <div class="section{{ $loop->first ? ' section-first' : '' }}">{{ $judulOrtu }}</div>
    <table class="rows">
        @foreach ($rowsOrtu as [$label, $value])
            <tr>
                <td class="label">{{ strtoupper($label) }}</td>
                <td class="colon">:</td>
                <td class="value">{{ $dash($value) }}</td>
            </tr>
        @endforeach
    </table>
@endforeach
</div>

<div class="page-break">
<div class="section section-first">Data jenjang sebelumnya</div>
<table class="rows">
    @foreach ($jenjangRows as [$label, $value])
        <tr>
            <td class="label">{{ strtoupper($label) }}</td>
            <td class="colon">:</td>
            <td class="value">{{ $dash($value) }}</td>
        </tr>
    @endforeach
</table>

<div class="section">Pernyataan kebenaran data</div>
<div class="pernyataan-box">
    <p>{{ $teksPoin1 }}</p>
    <p class="penutup">{{ $teksPenutupBiodata }}</p>
</div>

@include('siswa.partials.ttd-pernyataan', [
    'kota' => $madrasahKota,
    'tanggalSurat' => $tanggalSurat,
    'namaSiswa' => $siswa->nama,
    'nisn' => $siswa->nisn,
    'namaWali' => $namaWaliEfektif,
    'ttdSiswaDataUri' => $ttdSiswaDataUri,
    'ttdWaliDataUri' => $ttdWaliDataUri,
])
</div>

{{-- ========== SURAT PERNYATAAN ========== --}}
<div class="page-break">
<div class="surat-wrap">
<div class="surat-title">Surat Pernyataan Peserta Didik</div>

<div class="surat-body">
    <p>Yang bertanda tangan di bawah ini:</p>
    <table class="identitas-surat">
        <tr><td class="label">Nama</td><td class="colon">:</td><td>{{ $dash($siswa->nama) }}</td></tr>
        <tr><td class="label">NISN</td><td class="colon">:</td><td>{{ $dash($siswa->nisn) }}</td></tr>
        <tr><td class="label">NIS</td><td class="colon">:</td><td>{{ $dash($siswa->nis) }}</td></tr>
        <tr><td class="label">NIK</td><td class="colon">:</td><td>{{ $dash($siswa->nik) }}</td></tr>
        <tr><td class="label">Tempat, tanggal lahir</td><td class="colon">:</td><td>{{ $dash($ttl) }}</td></tr>
        <tr><td class="label">Jenis Kelamin</td><td class="colon">:</td><td>{{ $dash($jk) }}</td></tr>
        <tr><td class="label">Nama orang tua/wali</td><td class="colon">:</td><td>{{ $dash($namaWaliEfektif) }}</td></tr>
    </table>

    <p>Dengan ini menyatakan yang sesungguhnya bahwa sebagai peserta didik di MTsN 11 Majalengka, saya bersedia dan sanggup untuk:</p>
    <ol>
        <li>Mematuhi seluruh peraturan dan tata tertib yang berlaku di madrasah.</li>
        <li>Menjalankan ibadah serta menjaga akhlak mulia.</li>
        <li>Mengikuti seluruh kegiatan pembelajaran dan kegiatan madrasah lainnya dengan penuh tanggung jawab.</li>
        <li>Menjaga nama baik madrasah serta tidak melakukan perbuatan yang dapat merugikan diri sendiri maupun madrasah.</li>
    </ol>
    <p>Apabila di kemudian hari saya melanggar peraturan dan tata tertib yang berlaku di MTsN 11 Majalengka, saya bersedia menerima sanksi sesuai dengan ketentuan yang berlaku, termasuk sanksi pemberhentian sebagai peserta didik.</p>
    <p>Demikian surat pernyataan ini saya buat dengan sebenar-benarnya, dalam keadaan sadar, dan tanpa paksaan dari pihak mana pun untuk dapat dipergunakan sebagaimana mestinya.</p>
</div>

@include('siswa.partials.ttd-pernyataan', [
    'kota' => $madrasahKota,
    'tanggalSurat' => $tanggalSurat,
    'namaSiswa' => $siswa->nama,
    'nisn' => $siswa->nisn,
    'namaWali' => $namaWaliEfektif,
    'ttdSiswaDataUri' => $ttdSiswaDataUri,
    'ttdWaliDataUri' => $ttdWaliDataUri,
])
</div>
</div>

<div class="footer">
    dokumen digenerate oleh sistem MADANI MTsN 11 Majalengka · {{ $generatedAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
</div>
</body>
</html>
