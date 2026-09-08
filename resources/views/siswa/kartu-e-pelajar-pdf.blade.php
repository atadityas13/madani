<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu E-Pelajar — {{ $siswa->nama }}</title>
    <style>
        @page { margin: 40px 36px 48px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .sheet-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.4px;
            margin: 0 0 22px;
            color: #064E3B;
            text-transform: uppercase;
        }
        .cards {
            width: 100%;
            border-collapse: collapse;
        }
        .cards > tbody > tr > td {
            vertical-align: top;
            width: 48%;
        }
        .cards > tbody > tr > td.gap {
            width: 4%;
        }
        /* KTP / ID-1 approx at 96dpi: 85.6mm≈324px, 54mm≈204px — scaled for DomPDF */
        .card {
            width: 320px;
            height: 202px;
            border: 1.5px solid #047857;
            background: #fff;
        }
        .card-pad {
            padding: 8px 9px 7px;
        }
        .head {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .head td { vertical-align: middle; }
        .head .logo { width: 34px; }
        .head .logo img { width: 30px; height: auto; }
        .head .kop { text-align: center; padding: 0 4px; line-height: 1.15; }
        .head .l1 { font-size: 5.5px; font-weight: bold; color: #064E3B; }
        .head .l2 { font-size: 6.5px; font-weight: bold; color: #047857; margin-top: 1px; }
        .head .l3 { font-size: 8px; font-weight: bold; letter-spacing: 0.4px; margin-top: 2px; color: #064E3B; text-transform: uppercase; }
        .line {
            border: 0;
            border-top: 1.5px solid #047857;
            margin: 0 0 6px;
        }
        .body {
            width: 100%;
            border-collapse: collapse;
        }
        .body td { vertical-align: top; }
        .foto-cell { width: 62px; padding-right: 6px; }
        .foto, .foto-empty {
            width: 56px;
            height: 72px;
            border: 1px solid #999;
            display: block;
        }
        .foto-empty {
            background: #f1f5f9;
            text-align: center;
            color: #888;
            font-size: 7px;
            line-height: 72px;
        }
        .nama {
            font-size: 9px;
            font-weight: bold;
            color: #064E3B;
            margin: 0 0 3px;
            text-transform: uppercase;
        }
        .meta { width: 100%; border-collapse: collapse; }
        .meta td {
            padding: 0.5px 0;
            font-size: 7px;
            line-height: 1.25;
            vertical-align: top;
        }
        .meta .label { width: 42px; color: #555; }
        .meta .colon { width: 8px; }
        .meta .value { font-weight: bold; }
        .qr-cell { width: 58px; text-align: right; padding-left: 4px; }
        .qr-cell img { width: 52px; height: 52px; }
        .qr-cap { font-size: 5.5px; color: #666; text-align: center; margin-top: 2px; }
        .back {
            background: #ecfdf5;
            height: 202px;
        }
        .back-title {
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            color: #064E3B;
            text-transform: uppercase;
            margin: 0 0 6px;
        }
        .back-madrasah {
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            color: #047857;
            margin-bottom: 4px;
        }
        .back-text {
            text-align: center;
            font-size: 6.5px;
            color: #333;
            line-height: 1.35;
            margin: 0 0 4px;
        }
        .back-note {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid #a7f3d0;
            text-align: center;
            font-size: 6.5px;
            color: #555;
            line-height: 1.35;
        }
        .back-foot {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .back-foot td {
            vertical-align: middle;
            font-size: 6px;
            color: #666;
        }
        .back-foot img { width: 24px; height: auto; }
        .caption {
            text-align: center;
            font-size: 8px;
            color: #666;
            margin-top: 6px;
        }
        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -22px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 4px;
        }
    </style>
</head>
<body>
@php
    $dash = fn ($v) => filled($v) ? $v : '—';
@endphp

<div class="sheet-title">Preview Kartu E-Pelajar</div>

<table class="cards">
    <tr>
        <td>
            <div class="card">
                <div class="card-pad">
                    <table class="head">
                        <tr>
                            <td class="logo">
                                @if ($logoKemenagDataUri)
                                    <img src="{{ $logoKemenagDataUri }}" alt="Logo Kemenag">
                                @endif
                            </td>
                            <td class="kop">
                                <div class="l1">{{ $kartu['madrasah']['instansi_1'] }}</div>
                                <div class="l2">{{ $kartu['madrasah']['nama'] }}</div>
                                <div class="l3">Kartu E-Pelajar</div>
                            </td>
                            <td class="logo" style="text-align:right;">
                                @if ($logoDataUri)
                                    <img src="{{ $logoDataUri }}" alt="Logo madrasah">
                                @endif
                            </td>
                        </tr>
                    </table>
                    <hr class="line">
                    <table class="body">
                        <tr>
                            <td class="foto-cell">
                                @if ($fotoDataUri)
                                    <img class="foto" src="{{ $fotoDataUri }}" alt="Foto">
                                @else
                                    <div class="foto-empty">Foto</div>
                                @endif
                            </td>
                            <td>
                                <div class="nama">{{ $dash($kartu['nama']) }}</div>
                                <table class="meta">
                                    <tr><td class="label">NISN</td><td class="colon">:</td><td class="value">{{ $dash($kartu['nisn']) }}</td></tr>
                                    <tr><td class="label">NIS</td><td class="colon">:</td><td class="value">{{ $dash($kartu['nis']) }}</td></tr>
                                    <tr><td class="label">TTL</td><td class="colon">:</td><td class="value">{{ $dash($kartu['ttl']) }}</td></tr>
                                    <tr><td class="label">JK</td><td class="colon">:</td><td class="value">{{ $dash($kartu['jenis_kelamin_label']) }}</td></tr>
                                    <tr><td class="label">Alamat</td><td class="colon">:</td><td class="value">{{ $dash($kartu['alamat']) }}</td></tr>
                                </table>
                            </td>
                            <td class="qr-cell">
                                @if ($qrDataUri)
                                    <img src="{{ $qrDataUri }}" alt="QR">
                                    <div class="qr-cap">Scan verifikasi</div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="caption">Depan · 85,60 × 53,98 mm</div>
        </td>
        <td class="gap"></td>
        <td>
            <div class="card back">
                <div class="card-pad">
                    <div class="back-title">Keterangan</div>
                    <div class="back-madrasah">{{ $kartu['madrasah']['nama'] }}</div>
                    <div class="back-text">{{ $kartu['madrasah']['alamat'] ?: '—' }}</div>
                    @if (filled($kartu['madrasah']['kontak']))
                        <div class="back-text">{{ $kartu['madrasah']['kontak'] }}</div>
                    @endif
                    <div class="back-note">
                        Kartu ini adalah identitas resmi peserta didik {{ $kartu['madrasah']['nama_singkat'] }}.
                        Jika ditemukan, mohon dikembalikan ke madrasah. Keaslian kartu dapat diverifikasi melalui QR pada sisi depan.
                    </div>
                    <table class="back-foot">
                        <tr>
                            <td style="width:30px;">
                                @if ($logoDataUri)
                                    <img src="{{ $logoDataUri }}" alt="Logo">
                                @endif
                            </td>
                            <td style="text-align:right;">MADANI · {{ $kartu['madrasah']['nama_singkat'] }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="caption">Belakang · 85,60 × 53,98 mm</div>
        </td>
    </tr>
</table>

<div class="footer">
    Preview admin MADANI · {{ $generatedAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · ukuran kartu standar KTP (ID-1)
</div>
</body>
</html>
