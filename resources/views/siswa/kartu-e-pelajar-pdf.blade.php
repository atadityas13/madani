<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu E-Pelajar — {{ $siswa->nama }}</title>
    <style>
        @page { margin: 18mm 14mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #020617;
            font-size: 7px;
            margin: 0;
            padding: 0;
        }
        .sheet {
            width: 100%;
            border-collapse: collapse;
        }
        .sheet > tbody > tr > td {
            vertical-align: top;
            width: 48%;
        }
        .sheet > tbody > tr > td.gap {
            width: 4%;
        }

        /* ISO/IEC 7810 ID-1 — proporsi mirror Ta'lim */
        .card {
            width: 85.60mm;
            height: 53.98mm;
            border: 0.45mm solid #022C22;
            border-radius: 3.18mm;
            overflow: hidden;
            position: relative;
            background: #fff;
        }

        .front-header {
            position: absolute;
            left: 0;
            top: 0;
            width: 85.60mm;
            height: 14.84mm; /* 27.5% */
            background: #022C22;
            padding: 0.6mm 1.35mm;
        }
        .kop-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }
        .kop-table td { vertical-align: middle; }
        .kop-logo {
            width: 12.2mm;
            text-align: center;
        }
        .kop-logo img {
            width: 11.2mm;
            height: 11.2mm;
        }
        .kop-center {
            text-align: center;
            padding: 0 0.8mm;
            line-height: 1.1;
        }
        .kop-instansi {
            color: #FBBF24;
            font-size: 5px;
            font-weight: bold;
            letter-spacing: 0.12px;
        }
        .kop-nama {
            color: #FBBF24;
            font-size: 6.2px;
            font-weight: bold;
            letter-spacing: 0.18px;
            margin-top: 0.35mm;
        }
        .kop-meta {
            color: #ffffff;
            font-size: 4px;
            margin-top: 0.3mm;
        }

        .front-body {
            position: absolute;
            left: 0;
            top: 14.84mm;
            width: 85.60mm;
            height: 34.93mm; /* mid */
            background: #ffffff;
            overflow: hidden;
        }
        .body-pad {
            position: relative;
            width: 100%;
            height: 100%;
            padding: 1.9mm 1.35mm 0.4mm;
        }
        .ribbon {
            display: inline-block;
            background: #065F46;
            color: #ffffff;
            font-size: 5.4px;
            font-weight: bold;
            letter-spacing: 1.1px;
            padding: 0.85mm 7.5mm 0.85mm 2.2mm;
            /* DomPDF: bentuk slant Compose diganti radius */
            border-bottom-right-radius: 3.8mm;
        }
        .ribbon-gold {
            width: 26mm;
            height: 0.85mm;
            background: #F59E0B;
            margin-top: 0.5mm;
            margin-bottom: 1.1mm;
            border-bottom-right-radius: 1.8mm;
        }
        .front-main {
            width: 100%;
            border-collapse: collapse;
        }
        .front-main td { vertical-align: top; }
        .foto-cell { width: 17.2mm; padding-right: 1.5mm; }
        .foto, .foto-empty {
            width: 16.2mm;
            height: 21.6mm; /* ~62% midH, rasio 3:4 */
            border: 0.32mm solid #065F46;
            display: block;
        }
        .foto-empty {
            background: #f8fafc;
            text-align: center;
        }
        .foto-empty img {
            width: 8mm;
            height: auto;
            margin-top: 5.5mm;
        }
        .data-wrap {
            padding-right: 12.2mm; /* ruang QR overlay */
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table td {
            padding: 0.12mm 0;
            font-size: 5.7px;
            line-height: 1.18;
            vertical-align: top;
        }
        .data-label {
            width: 10.2mm;
            color: #020617;
            font-weight: bold;
            text-transform: uppercase;
        }
        .data-colon {
            width: 1.8mm;
            font-weight: bold;
            color: #020617;
        }
        .data-value {
            font-weight: bold;
            color: #020617;
        }
        .qr-box {
            position: absolute;
            top: 1.9mm;
            right: 1.35mm;
            width: 10.5mm;
            height: 10.5mm;
            border: 0.18mm solid #D1FAE5;
            background: #fff;
            padding: 0.35mm;
            text-align: center;
        }
        .qr-box img {
            width: 9.6mm;
            height: 9.6mm;
        }
        .caption-row {
            position: absolute;
            left: 1.35mm;
            right: 1.35mm;
            bottom: 0.55mm;
            height: 3.6mm;
        }
        .caption-row table {
            width: 100%;
            border-collapse: collapse;
        }
        .caption-row td { vertical-align: middle; }
        .caption-text {
            font-size: 4px;
            font-weight: bold;
            color: #020617;
        }
        .caption-logo {
            width: 14mm;
            text-align: right;
        }
        .caption-logo img {
            height: 3.2mm;
            width: auto;
        }

        .front-footer {
            position: absolute;
            left: 0;
            bottom: 0;
            width: 85.60mm;
            height: 4.21mm; /* 7.8% */
            background: #022C22;
            color: #ffffff;
            text-align: center;
            font-size: 3.2px;
            line-height: 1.12;
            padding: 0.55mm 1.5mm 0.35mm;
        }

        .back {
            background: #ffffff;
        }
        .back-bg {
            position: absolute;
            left: 0;
            top: 0;
            width: 85.60mm;
            height: 53.98mm;
            z-index: 0;
        }
        .back-bg img {
            width: 85.60mm;
            height: 53.98mm;
            opacity: 0.48;
        }
        .back-wash {
            position: absolute;
            left: 0;
            top: 0;
            width: 85.60mm;
            height: 53.98mm;
            background: rgba(255, 255, 255, 0.58);
            z-index: 1;
        }
        .back-inner {
            position: relative;
            z-index: 2;
            height: 53.98mm;
        }
        .back-header {
            height: 7.02mm;
            background: #022C22;
            text-align: center;
            color: #FBBF24;
            font-size: 7.2px;
            font-weight: bold;
            letter-spacing: 1.4px;
            line-height: 7.02mm;
        }
        .back-gold {
            height: 0.7mm;
            background: #F59E0B;
        }
        .back-content {
            height: 41.1mm;
            padding: 2mm 4mm;
            text-align: center;
        }
        .back-lead {
            color: #022C22;
            font-size: 6px;
            font-weight: bold;
            margin-bottom: 2.2mm;
        }
        .back-item {
            color: #020617;
            font-size: 6.4px;
            font-weight: bold;
            margin: 1.35mm 0;
            letter-spacing: 0.1px;
        }
        .back-footer {
            height: 4.86mm;
            background: #022C22;
            color: #ffffff;
            text-align: center;
            font-size: 4.2px;
            font-weight: bold;
            letter-spacing: 0.6px;
            line-height: 4.86mm;
        }
    </style>
</head>
<body>
@php
    $dash = fn ($v) => filled($v) ? $v : '—';
    $namaSingkat = filled($kartu['madrasah']['nama_singkat'])
        ? $kartu['madrasah']['nama_singkat']
        : ($kartu['madrasah']['nama'] ?: 'Madrasah');
    $ikrarItems = [
        'Belajar dengan baik',
        'Menghormati orang tua',
        'Menghormati guru',
        'Rukun sama teman',
        'Mencintai tanah air Indonesia',
    ];
@endphp

<table class="sheet">
    <tr>
        <td>
            <div class="card">
                <div class="front-header">
                    <table class="kop-table">
                        <tr>
                            <td class="kop-logo">
                                @if ($logoKemenagDataUri)
                                    <img src="{{ $logoKemenagDataUri }}" alt="Logo Kemenag">
                                @endif
                            </td>
                            <td class="kop-center">
                                <div class="kop-instansi">{{ $kartu['madrasah']['instansi_1'] }}</div>
                                <div class="kop-instansi">{{ $kartu['madrasah']['instansi_2'] }}</div>
                                <div class="kop-nama">{{ $kartu['madrasah']['nama'] }}</div>
                                @if (filled($kartu['madrasah']['alamat']))
                                    <div class="kop-meta">{{ $kartu['madrasah']['alamat'] }}</div>
                                @endif
                                @if (filled($kartu['madrasah']['kontak']))
                                    <div class="kop-meta">{{ $kartu['madrasah']['kontak'] }}</div>
                                @endif
                            </td>
                            <td class="kop-logo">
                                @if ($logoDataUri)
                                    <img src="{{ $logoDataUri }}" alt="Logo madrasah">
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="front-body">
                    <div class="body-pad">
                        <div class="ribbon">KARTU PELAJAR</div>
                        <div class="ribbon-gold"></div>

                        <table class="front-main">
                            <tr>
                                <td class="foto-cell">
                                    @if ($fotoDataUri)
                                        <img class="foto" src="{{ $fotoDataUri }}" alt="Foto">
                                    @elseif ($fotoPlaceholderDataUri)
                                        <div class="foto-empty">
                                            <img src="{{ $fotoPlaceholderDataUri }}" alt="">
                                        </div>
                                    @else
                                        <div class="foto-empty"></div>
                                    @endif
                                </td>
                                <td>
                                    <div class="data-wrap">
                                        <table class="data-table">
                                            <tr>
                                                <td class="data-label">NAMA</td>
                                                <td class="data-colon">:</td>
                                                <td class="data-value">{{ mb_strtoupper((string) $dash($kartu['nama'])) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="data-label">NISN</td>
                                                <td class="data-colon">:</td>
                                                <td class="data-value">{{ $dash($kartu['nisn']) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="data-label">NIS</td>
                                                <td class="data-colon">:</td>
                                                <td class="data-value">{{ $dash($kartu['nis']) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="data-label">TTL</td>
                                                <td class="data-colon">:</td>
                                                <td class="data-value">{{ $dash($kartu['ttl']) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="data-label">JK</td>
                                                <td class="data-colon">:</td>
                                                <td class="data-value">{{ $dash($kartu['jenis_kelamin_label']) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="data-label">ALAMAT</td>
                                                <td class="data-colon">:</td>
                                                <td class="data-value">{{ $dash($kartu['alamat']) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        @if ($qrDataUri)
                            <div class="qr-box">
                                <img src="{{ $qrDataUri }}" alt="QR">
                            </div>
                        @endif

                        <div class="caption-row">
                            <table>
                                <tr>
                                    <td class="caption-text">Berlaku selama menjadi siswa {{ $namaSingkat }}</td>
                                    <td class="caption-logo">
                                        @if ($logoMadaniDataUri)
                                            <img src="{{ $logoMadaniDataUri }}" alt="MADANI">
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="front-footer">
                    Kartu Pelajar ini dihasilkan oleh sistem resmi {{ $namaSingkat }}
                    dan merupakan dokumen yang sah serta dapat dipergunakan untuk keperluan
                    administrasi akademik maupun nonakademik.
                </div>
            </div>
        </td>
        <td class="gap"></td>
        <td>
            <div class="card back">
                @if ($bgBelakangDataUri)
                    <div class="back-bg">
                        <img src="{{ $bgBelakangDataUri }}" alt="">
                    </div>
                @endif
                <div class="back-wash"></div>
                <div class="back-inner">
                    <div class="back-header">IKRAR PELAJAR INDONESIA</div>
                    <div class="back-gold"></div>
                    <div class="back-content">
                        <div class="back-lead">Kami Pelajar Indonesia, berikrar untuk:</div>
                        @foreach ($ikrarItems as $i => $item)
                            <div class="back-item">{{ $i + 1 }}.&nbsp;&nbsp;{{ $item }}</div>
                        @endforeach
                    </div>
                    <div class="back-footer">{{ mb_strtoupper((string) $namaSingkat) }}</div>
                </div>
            </div>
        </td>
    </tr>
</table>
</body>
</html>
