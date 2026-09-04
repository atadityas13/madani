<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Portofolio {{ $siswa->nama }}</title>
    <style>
        @page { margin: 28px 32px 40px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111;
            line-height: 1.35;
        }
        .kop { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .kop td { vertical-align: middle; }
        .kop-logo { width: 72px; }
        .kop-logo img { width: 68px; height: auto; }
        .kop-text { text-align: center; padding: 0 8px; }
        .kop-text .line1 { font-size: 11px; font-weight: bold; letter-spacing: 0.3px; }
        .kop-text .line2 { font-size: 11px; font-weight: bold; }
        .kop-text .line3 { font-size: 12px; font-weight: bold; margin-top: 1px; }
        .kop-text .line4, .kop-text .line5 { font-size: 9px; margin-top: 1px; }
        .kop-qr { width: 78px; text-align: right; }
        .kop-qr img { width: 70px; height: 70px; }
        .kop-line {
            border: 0;
            border-top: 3px solid #111;
            border-bottom: 1px solid #111;
            margin: 4px 0 12px;
        }
        .title-wrap { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .title-wrap td { vertical-align: top; }
        .title-name { font-size: 16px; font-weight: bold; padding-top: 4px; }
        .foto-box { width: 90px; text-align: right; }
        .foto-box img {
            width: 80px;
            height: 100px;
            object-fit: cover;
            border: 1px solid #999;
        }
        .foto-placeholder {
            display: inline-block;
            width: 80px;
            height: 100px;
            border: 1px solid #999;
            text-align: center;
            line-height: 100px;
            color: #888;
            font-size: 9px;
        }
        .section { font-size: 11px; font-weight: bold; margin: 12px 0 4px; text-transform: uppercase; }
        .rows { width: 100%; border-collapse: collapse; }
        .rows td { padding: 2px 0; vertical-align: top; }
        .rows .label { width: 38%; }
        .rows .colon { width: 12px; }
        .rows .value { }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 9px;
        }
        table.data th, table.data td {
            border: 1px solid #333;
            padding: 3px 4px;
            text-align: left;
            vertical-align: top;
        }
        table.data th { background: #efefef; font-weight: bold; }
        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -18px;
            font-size: 8px;
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
    $alamatKop = collect([
        $madrasah->alamat,
        $madrasah->desa ? 'Desa '.$madrasah->desa : null,
        $madrasah->kecamatan ? 'Kec. '.$madrasah->kecamatan : null,
        $madrasah->kota ? 'Kab. '.$madrasah->kota : null,
        $madrasah->kode_pos,
    ])->filter()->implode(' ');
    $kontakKop = collect([
        $madrasah->telepon ? 'Telp / Fax: '.$madrasah->telepon : null,
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
            @if ($alamatKop !== '')
                <div class="line4">{{ $alamatKop }}</div>
            @endif
            @if ($kontakKop !== '')
                <div class="line5">{{ $kontakKop }}</div>
            @endif
        </td>
        <td class="kop-qr">
            <div style="width:70px;height:70px;">{!! $qrSvg !!}</div>
        </td>
    </tr>
</table>
<hr class="kop-line">

<table class="title-wrap">
    <tr>
        <td>
            <div class="title-name">{{ strtoupper($siswa->nama) }}</div>
        </td>
        <td class="foto-box">
            @if ($fotoDataUri)
                <img src="{{ $fotoDataUri }}" alt="Foto siswa">
            @else
                <span class="foto-placeholder">Foto</span>
            @endif
        </td>
    </tr>
</table>

<div class="section">Data siswa</div>
<table class="rows">
    @foreach ($identitasRows as [$label, $value])
        <tr>
            <td class="label">{{ strtoupper($label) }}</td>
            <td class="colon">:</td>
            <td class="value">{{ $dash($value) }}</td>
        </tr>
    @endforeach
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

@foreach ([['Ayah kandung', $ayahRows], ['Ibu kandung', $ibuRows], ['Wali', $waliRows]] as [$judulOrtu, $rowsOrtu])
    <div class="section">{{ $judulOrtu }}</div>
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

<div class="section">Aktivitas belajar</div>
<table class="data">
    <thead>
        <tr>
            <th>Tahun ajaran</th>
            <th>Tingkat</th>
            <th>Rombel</th>
            <th>Status</th>
            <th>Keterangan</th>
            <th>NSM</th>
            <th>NPSN</th>
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
                <td>{{ $dash($row['nsm'] ?? null) }}</td>
                <td>{{ $dash($row['npsn'] ?? null) }}</td>
            </tr>
        @empty
            <tr><td colspan="7">-</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section">Beasiswa &amp; bantuan</div>
<table class="data">
    <thead>
        <tr>
            <th>Tahun</th>
            <th>Kategori</th>
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

<div class="footer">
    dokumen digenerate oleh sistem MADANI MTsN 11 Majalengka · {{ $generatedAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
</div>
</body>
</html>
