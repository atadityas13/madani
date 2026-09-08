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

        /* ISO/IEC 7810 ID-1 (sama seperti Ta'lim) */
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
            height: 14.84mm; /* 27.5% */
            background: #022C22;
            padding: 0.7mm 1.4mm;
        }
        .kop-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }
        .kop-table td { vertical-align: middle; }
        .kop-logo {
            width: 11.5mm;
            text-align: center;
        }
        .kop-logo img {
            width: 10.5mm;
            height: 10.5mm;
            object-fit: contain;
        }
        .kop-center {
            text-align: center;
            padding: 0 1mm;
            line-height: 1.12;
        }
        .kop-instansi {
            color: #FBBF24;
            font-size: 5.2px;
            font-weight: bold;
            letter-spacing: 0.15px;
        }
        .kop-nama {
            color: #FBBF24;
            font-size: 6.4px;
            font-weight: bold;
            letter-spacing: 0.2px;
            margin-top: 0.4mm;
        }
        .kop-meta {
            color: #ffffff;
            font-size: 4.2px;
            margin-top: 0.35mm;
        }

        .front-body {
            height: 34.93mm; /* sisa setelah header+footer */
            position: relative;
            background: #ffffff;
            padding: 1.6mm 1.4mm 0.8mm;
        }
        .ribbon {
            display: inline-block;
            background: #065F46;
            color: #ffffff;
            font-size: 5.6px;
            font-weight: bold;
            letter-spacing: 1.2px;
            padding: 0.9mm 8mm 0.9mm 2.4mm;
            border-bottom-right-radius: 4mm;
        }
        .ribbon-gold {
            width: 28mm;
            height: 0.9mm;
            background: #F59E0B;
            margin-top: 0.55mm;
            margin-bottom: 1.2mm;
            border-bottom-right-radius: 2mm;
        }
        .front-main {
            width: 100%;
            border-collapse: collapse;
        }
        .front-main td { vertical-align: top; }
        .foto-cell { width: 16mm; padding-right: 1.4mm; }
        .foto, .foto-empty {
            width: 14.5mm;
            height: 19.3mm;
            border: 0.35mm solid #065F46;
            display: block;
        }
        .foto-empty {
            background: #f8fafc;
            text-align: center;
            color: #94a3b8;
            font-size: 5px;
            line-height: 19.3mm;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-right: 14mm;
        }
        .data-table td {
            padding: 0.15mm 0;
            font-size: 5.8px;
            line-height: 1.2;
            vertical-align: top;
        }
        .data-label {
            width: 11mm;
            color: #022C22;
            font-weight: bold;
            text-transform: uppercase;
        }
        .data-colon { width: 2mm; font-weight: bold; }
        .data-value { font-weight: bold; color: #020617; }
        .qr-box {
            position: absolute;
            top: 1.6mm;
            right: 1.4mm;
            width: 12mm;
            height: 12mm;
            border: 0.2mm solid #D1FAE5;
            background: #fff;
            padding: 0.4mm;
            text-align: center;
        }
        .qr-box img {
            width: 11mm;
            height: 11mm;
        }
        .caption-row {
            position: absolute;
            left: 1.4mm;
            right: 1.4mm;
            bottom: 0.6mm;
            height: 4mm;
        }
        .caption-row table {
            width: 100%;
            border-collapse: collapse;
        }
        .caption-row td { vertical-align: middle; }
        .caption-text {
            font-size: 4.2px;
            font-weight: bold;
            color: #020617;
        }
        .caption-logo {
            width: 10mm;
            text-align: right;
        }
        .caption-logo img {
            height: 3.4mm;
            width: auto;
        }

        .front-footer {
            height: 4.21mm; /* 7.8% */
            background: #022C22;
            color: #ffffff;
            text-align: center;
            font-size: 3.4px;
            line-height: 1.15;
            padding: 0.45mm 1.6mm;
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
            height: 7.02mm; /* ~13% */
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
            height: 4.86mm; /* ~9% */
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
                    <div class="ribbon">KARTU PELAJAR</div>
                    <div class="ribbon-gold"></div>

                    <table class="front-main">
                        <tr>
                            <td class="foto-cell">
                                @if ($fotoDataUri)
                                    <img class="foto" src="{{ $fotoDataUri }}" alt="Foto">
                                @else
                                    <div class="foto-empty">Foto</div>
                                @endif
                            </td>
                            <td>
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
