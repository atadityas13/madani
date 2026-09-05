<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Biodata dan Surat Pernyataan — {{ $siswa->nama }}</title>
    <style>
        @page { margin: 40px 54px 48px; }
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
        .kop-qr { width: 82px; text-align: right; }
        .kop-qr img { width: 74px; height: 74px; }
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
        .foto-float {
            float: right;
            width: 90px;
            margin: 0 0 10px 14px;
            text-align: right;
        }
        .foto-float img {
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
        .clear { clear: both; height: 0; }
        .section { font-size: 12px; font-weight: bold; margin: 10px 0 3px; text-transform: uppercase; }
        .section-first { margin-top: 0; margin-bottom: 3px; }
        .page-break { page-break-before: always; }
        .page-break .section:first-child { margin-top: 0; }
        .ortu-block { page-break-inside: avoid; }
        .rows { width: 100%; border-collapse: collapse; }
        .rows td { padding: 2px 0; vertical-align: top; font-size: 11px; }
        .rows .label { width: 38%; }
        .rows .colon { width: 14px; text-align: left; }
        .pernyataan-box {
            margin-top: 0;
            text-align: justify;
            font-size: 11px;
            line-height: 1.45;
        }
        .pernyataan-box p { margin: 0 0 6px; }
        .pernyataan-box .penutup { margin: 0; }
        .surat-wrap { padding: 28px 32px 0; }
        .surat-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin: 0 0 28px;
            letter-spacing: 0.5px;
        }
        .surat-body { text-align: justify; font-size: 11px; line-height: 1.5; }
        .surat-body p { margin: 0 0 6px; }
        .surat-body ol { margin: 6px 0 6px 18px; padding: 0; }
        .surat-body li { margin-bottom: 3px; }
        .identitas-surat { width: 100%; border-collapse: collapse; margin: 8px 0 10px; }
        .identitas-surat td { padding: 1.5px 0; vertical-align: top; }
        .identitas-surat .label { width: 38%; }
        .identitas-surat .colon { width: 14px; }
        .ttd-table { width: 100%; border-collapse: collapse; margin-top: 34px; }
        .ttd-table > tbody > tr > td { vertical-align: top; width: 50%; padding: 0; }
        .ttd-inner { width: 230px; border-collapse: collapse; }
        .ttd-inner td {
            text-align: left;
            font-size: 11px;
            line-height: 1.15;
            padding: 0;
        }
        .ttd-date { white-space: nowrap; }
        .ttd-img {
            height: 64px;
            width: auto;
            max-width: 160px;
            margin: 8px 0 4px;
            display: block;
        }
        .ttd-spacer { width: 120px; height: 64px; margin: 8px 0 4px; }
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
    $identitasAll = array_merge([['Nama', $siswa->nama]], $identitasRows);
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
        <td class="kop-qr">
            @if ($qrDataUri)
                <img src="{{ $qrDataUri }}" alt="QR verifikasi">
            @endif
        </td>
    </tr>
</table>
<hr class="kop-line">

<div class="doc-title">Biodata Siswa</div>

<div class="foto-float">
    @if ($fotoDataUri)
        <img src="{{ $fotoDataUri }}" alt="Foto siswa">
    @else
        <table class="foto-placeholder"><tr><td>Foto</td></tr></table>
    @endif
</div>

<div class="section section-first">Data siswa</div>
<table class="rows" width="100%">
    @foreach ($identitasAll as [$label, $value])
        <tr>
            <td class="label" width="38%">{{ strtoupper($label) }}</td>
            <td class="colon" width="2%">:</td>
            <td class="value" width="60%">{{ $dash($value) }}</td>
        </tr>
    @endforeach
</table>
<div class="clear"></div>

<div class="section">Alamat siswa</div>
<table class="rows" width="100%">
    @foreach ($alamatRows as [$label, $value])
        <tr>
            <td class="label" width="38%">{{ strtoupper($label) }}</td>
            <td class="colon" width="2%">:</td>
            <td class="value" width="60%">{{ $dash($value) }}</td>
        </tr>
    @endforeach
</table>

<div class="page-break">
@foreach ([['Ayah kandung', $ayahRows], ['Ibu kandung', $ibuRows], ['Wali', $waliRows]] as [$judulOrtu, $rowsOrtu])
    <div class="ortu-block">
        <div class="section{{ $loop->first ? ' section-first' : '' }}">{{ $judulOrtu }}</div>
        <table class="rows" width="100%">
            @foreach ($rowsOrtu as [$label, $value])
                <tr>
                    <td class="label" width="38%">{{ strtoupper($label) }}</td>
                    <td class="colon" width="2%">:</td>
                    <td class="value" width="60%">{{ $dash($value) }}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endforeach
</div>

<div class="page-break">
<div class="section section-first">Data jenjang sebelumnya</div>
<table class="rows" width="100%">
    @foreach ($jenjangRows as [$label, $value])
        <tr>
            <td class="label" width="38%">{{ strtoupper($label) }}</td>
            <td class="colon" width="2%">:</td>
            <td class="value" width="60%">{{ $dash($value) }}</td>
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
    <table class="identitas-surat" width="100%">
        <tr>
            <td class="label" width="38%">Nama</td>
            <td class="colon" width="2%">:</td>
            <td width="60%">{{ $dash($siswa->nama) }}</td>
        </tr>
        <tr>
            <td class="label" width="38%">NISN</td>
            <td class="colon" width="2%">:</td>
            <td width="60%">{{ $dash($siswa->nisn) }}</td>
        </tr>
        <tr>
            <td class="label" width="38%">NIS</td>
            <td class="colon" width="2%">:</td>
            <td width="60%">{{ $dash($siswa->nis) }}</td>
        </tr>
        <tr>
            <td class="label" width="38%">NIK</td>
            <td class="colon" width="2%">:</td>
            <td width="60%">{{ $dash($siswa->nik) }}</td>
        </tr>
        <tr>
            <td class="label" width="38%">Tempat, tanggal lahir</td>
            <td class="colon" width="2%">:</td>
            <td width="60%">{{ $dash($ttl) }}</td>
        </tr>
        <tr>
            <td class="label" width="38%">Jenis Kelamin</td>
            <td class="colon" width="2%">:</td>
            <td width="60%">{{ $dash($jk) }}</td>
        </tr>
        <tr>
            <td class="label" width="38%">Nama orang tua/wali</td>
            <td class="colon" width="2%">:</td>
            <td width="60%">{{ $dash($namaWaliEfektif) }}</td>
        </tr>
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
