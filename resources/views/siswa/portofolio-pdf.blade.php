<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Portofolio {{ $siswa->nama }}</title>
    <style>
        @page { margin: 36px 48px 44px; }
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
        .section { font-size: 12px; font-weight: bold; margin: 12px 0 4px; text-transform: uppercase; }
        .section-first { margin-top: 0; margin-bottom: 4px; }
        .page-break { page-break-before: always; }
        .page-break .section:first-child { margin-top: 0; }
        .ortu-block { page-break-inside: avoid; }
        .rows { width: 100%; border-collapse: collapse; }
        .rows td { padding: 2.5px 0; vertical-align: top; font-size: 11px; }
        .rows .label { width: 38%; }
        .rows .colon { width: 14px; }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 10px;
        }
        table.data th, table.data td {
            border: 1px solid #333;
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
        }
        table.data th { background: #efefef; font-weight: bold; }
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
@endphp

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

<div class="doc-title">PORTOFOLIO SISWA</div>

<div class="foto-float">
    @if ($fotoDataUri)
        <img src="{{ $fotoDataUri }}" alt="Foto siswa">
    @else
        <table class="foto-placeholder"><tr><td>Foto</td></tr></table>
    @endif
</div>

<div class="section section-first">Data siswa</div>
<table class="rows" width="100%">
    <tr>
        <td class="label" width="38%">NAMA</td>
        <td class="colon" width="2%">:</td>
        <td class="value" width="60%">{{ $dash($siswa->nama) }}</td>
    </tr>
    @foreach ($identitasRows as [$label, $value])
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
<div class="section section-first">Aktivitas belajar</div>
<table class="data">
    <thead>
        <tr>
            <th>Tahun ajaran</th>
            <th>Tingkat</th>
            <th>Rombel</th>
            <th>Status</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($aktivitas as $row)
            <tr>
                <td>{{ $dash($row['tahun_ajaran'] ?? null) }}</td>
                <td>{{ $dash($row['tingkat'] ?? null) }}</td>
                <td>{{ $dash($row['rombel'] ?? null) }}</td>
                <td>{{ $dash($row['status'] ?? null) }}</td>
                <td>{{ $dash($row['keterangan'] ?? null) }}</td>
            </tr>
        @empty
            <tr><td colspan="5">-</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section">Beasiswa &amp; bantuan</div>
<table class="data">
    <thead>
        <tr>
            <th>Tahun</th>
            <th>Jenis bantuan</th>
            <th>Nama</th>
            <th>Nominal</th>
            <th>No rekening</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($beasiswas as $item)
            <tr>
                <td>{{ $dash($item->tahun !== null ? (string) $item->tahun : null) }}</td>
                <td>{{ $dash($item->kategori) }}</td>
                <td>{{ $dash($item->nama) }}</td>
                <td>{{ $dash($item->nominal !== null ? number_format((int) $item->nominal, 0, ',', '.') : null) }}</td>
                <td>{{ $dash($item->nomor_rekening) }}</td>
            </tr>
        @empty
            <tr><td colspan="5">-</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section">Prestasi</div>
<table class="data">
    <thead>
        <tr>
            <th>Tahun</th>
            <th>Nama</th>
            <th>Jenis</th>
            <th>Tingkat</th>
            <th>Penyelenggara</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($prestasis as $item)
            <tr>
                <td>{{ $dash($item->tahun !== null ? (string) $item->tahun : null) }}</td>
                <td>{{ $dash($item->nama) }}</td>
                <td>{{ $dash($item->jenis) }}</td>
                <td>{{ $dash($item->tingkat) }}</td>
                <td>{{ $dash($item->penyelenggara) }}</td>
            </tr>
        @empty
            <tr><td colspan="5">-</td></tr>
        @endforelse
    </tbody>
</table>
</div>

<div class="footer">
    dokumen digenerate oleh sistem MADANI MTsN 11 Majalengka · {{ $generatedAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
</div>
</body>
</html>
