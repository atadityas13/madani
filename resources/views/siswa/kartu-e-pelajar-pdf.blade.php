<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu E-Pelajar — {{ $siswa->nama }}</title>
    <style>
        /* ISO/IEC 7810 ID-1: 85.60 × 53.98 mm = 242.65 × 153.0 pt */
        @page { margin: 36pt 24pt; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #020617;
            font-size: 7pt;
            margin: 0;
            padding: 0;
        }
        .sheet { width: 100%; border-collapse: collapse; }
        .sheet > tbody > tr > td { vertical-align: top; width: 48%; }
        .sheet > tbody > tr > td.gap { width: 4%; }

        .card {
            width: 240.45pt;
            height: 150.8pt;
            overflow: hidden;
            border: 1.1pt solid #022C22;
            background: #ffffff;
        }

        table.inner {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.inner > tbody > tr > td { padding: 0; margin: 0; }

        /* hdr 35 + bdy 93 + cap 8.8 = 136.8; ftr-abs 14 → 150.8 */
        td.hdr {
            background: #022C22;
            height: 35pt;
            padding: 0 !important;
        }
        table.kop { width: 100%; border-collapse: collapse; }
        td.kop-logo {
            width: 34pt;
            background: #022C22;
            text-align: center;
            vertical-align: middle !important;
            padding: 0.5pt 0.8pt !important;
        }
        td.kop-logo img { width: 30pt; height: 30pt; }
        td.kop-text {
            background: #022C22;
            text-align: center;
            vertical-align: middle !important;
            padding: 1.2pt 0.5pt !important;
            line-height: 1.08;
        }
        .kop-l1, .kop-l2 {
            color: #FBBF24;
            font-size: 4.1pt;
            font-weight: bold;
            white-space: nowrap;
        }
        .kop-l3 {
            color: #FBBF24;
            font-size: 4.9pt;
            font-weight: bold;
            margin-top: 0.3pt;
            white-space: nowrap;
        }
        .kop-l4, .kop-l5 {
            color: #ffffff;
            font-size: 3.25pt;
            margin-top: 0.25pt;
            white-space: nowrap;
        }

        td.bdy {
            background: #ffffff;
            height: 93pt;
            padding: 2pt 1.2pt 1pt 3pt !important;
            vertical-align: top !important;
        }
        table.body-fill {
            width: 100%;
            border-collapse: collapse;
        }
        table.body-fill > tbody > tr > td { padding: 0; }
        td.body-main { vertical-align: top !important; }

        table.top-row { width: 100%; border-collapse: collapse; }
        table.top-row > tbody > tr > td { vertical-align: top; padding: 0; }
        td.top-left { padding-right: 2pt !important; }
        td.top-qr {
            width: 36pt;
            text-align: right;
            vertical-align: top !important;
            padding: 0 !important;
        }
        td.top-qr img {
            width: 34pt;
            height: 34pt;
            border: 0.45pt solid #D1FAE5;
            padding: 0.8pt;
            background: #ffffff;
            display: block;
            margin: 0;
        }

        /* Badge miring ala Ta'lim (aproksimasi DomPDF) */
        table.ribbon { border-collapse: collapse; }
        td.ribbon-main {
            background: #065F46;
            color: #ffffff;
            font-size: 5.0pt;
            font-weight: bold;
            letter-spacing: 0.85pt;
            padding: 1.8pt 3pt 1.8pt 5pt !important;
            white-space: nowrap;
            vertical-align: middle !important;
        }
        td.ribbon-cut {
            width: 0;
            height: 0;
            padding: 0 !important;
            border-style: solid;
            border-width: 7.4pt 0 0 11pt;
            border-color: #065F46 transparent transparent transparent;
            font-size: 1pt;
            line-height: 1pt;
            vertical-align: top !important;
        }
        .ribbon-gold {
            width: 48pt;
            height: 2.2pt;
            background: #F59E0B;
            margin: 1.3pt 0 2pt 0;
        }

        table.main { width: 100%; border-collapse: collapse; }
        table.main > tbody > tr > td { vertical-align: top; padding: 0; }
        td.foto-col { width: 44pt; padding-right: 2.5pt !important; }
        img.foto, .foto-box {
            width: 41pt;
            height: 54pt;
            border: 0.85pt solid #065F46;
            display: block;
        }
        .foto-box { background: #f8fafc; text-align: center; }
        .foto-box img { width: 16pt; margin-top: 16pt; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data td {
            padding: 0.35pt 0;
            font-size: 4.7pt;
            line-height: 1.28;
            letter-spacing: 0.1pt;
            vertical-align: top;
            font-weight: bold;
            color: #020617;
        }
        table.data tr:not(.row-alamat) td { white-space: nowrap; }
        td.lbl { width: 22pt; text-transform: uppercase; }
        td.col { width: 4.5pt; }
        td.val-alamat {
            font-size: 4.35pt;
            line-height: 1.28;
            letter-spacing: 0.1pt;
        }

        /* Alamat full-width di bawah QR */
        table.alamat-wrap { width: 100%; border-collapse: collapse; margin-top: 0.4pt; }
        table.alamat-wrap td {
            padding: 0.35pt 0;
            font-size: 4.7pt;
            line-height: 1.28;
            letter-spacing: 0.1pt;
            font-weight: bold;
            color: #020617;
            vertical-align: top;
        }

        td.cap-row {
            background: #ffffff;
            height: 8.8pt;
            padding: 0 4.5pt 0.2pt 3pt !important;
            vertical-align: middle !important;
        }
        table.cap { width: 100%; border-collapse: collapse; }
        table.cap td { vertical-align: middle !important; padding: 0 !important; }
        td.cap-txt { font-size: 3.2pt; font-weight: bold; color: #020617; white-space: nowrap; }
        td.cap-logo { width: 52pt; text-align: right; padding-right: 4pt !important; }
        td.cap-logo img { height: 10.5pt; width: auto; }

        .card-front { position: relative; }
        .card-front-body {
            height: 136.8pt;
            overflow: hidden;
        }
        .ftr-abs {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 14pt;
            background: #022C22;
            color: #ffffff;
            text-align: center;
            font-size: 2.45pt;
            line-height: 14pt;
            white-space: nowrap;
            letter-spacing: -0.025pt;
            font-weight: normal;
        }

        /* Belakang */
        .card-back { position: relative; }
        .bf-abs {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 14pt;
            background: #022C22;
            color: #ffffff;
            text-align: center;
            font-size: 5.6pt;
            font-weight: bold;
            letter-spacing: 0.85pt;
            line-height: 14pt;
            white-space: nowrap;
        }
        td.bh {
            background: #022C22;
            color: #FBBF24;
            text-align: center;
            height: 20pt;
            font-size: 6.8pt;
            font-weight: bold;
            letter-spacing: 0.9pt;
            padding: 3.5pt 4pt !important;
            vertical-align: middle !important;
        }
        td.bgold { height: 1.8pt; background: #F59E0B; font-size: 1pt; line-height: 1pt; }
        td.bc {
            height: 129pt;
            text-align: center;
            vertical-align: middle !important;
            padding: 4pt 8pt 16pt 8pt !important;
            background-color: #f3f4f6;
        }
        .bc-lead {
            color: #022C22;
            font-size: 5.4pt;
            font-weight: bold;
            margin-bottom: 3.5pt;
        }
        .bc-item {
            color: #020617;
            font-size: 5.8pt;
            font-weight: bold;
            margin: 2.3pt 0;
            white-space: nowrap;
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
            <div class="card card-front">
                <div class="card-front-body">
                <table class="inner">
                    <tr>
                        <td class="hdr">
                            <table class="kop">
                                <tr>
                                    <td class="kop-logo">
                                        @if ($logoKemenagDataUri)
                                            <img src="{{ $logoKemenagDataUri }}" width="30" height="30" alt="Kemenag">
                                        @endif
                                    </td>
                                    <td class="kop-text">
                                        <div class="kop-l1">{{ $kartu['madrasah']['instansi_1'] }}</div>
                                        <div class="kop-l2">{{ $kartu['madrasah']['instansi_2'] }}</div>
                                        <div class="kop-l3">{{ $kartu['madrasah']['nama'] }}</div>
                                        @if (filled($kartu['madrasah']['alamat']))
                                            <div class="kop-l4">{{ $kartu['madrasah']['alamat'] }}</div>
                                        @endif
                                        @if (filled($kartu['madrasah']['kontak']))
                                            <div class="kop-l5">{{ $kartu['madrasah']['kontak'] }}</div>
                                        @endif
                                    </td>
                                    <td class="kop-logo">
                                        @if ($logoDataUri)
                                            <img src="{{ $logoDataUri }}" width="30" height="30" alt="Madrasah">
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="bdy">
                            <table class="body-fill">
                                <tr>
                                    <td class="body-main">
                                        <table class="top-row">
                                            <tr>
                                                <td class="top-left">
                                                    <table class="ribbon">
                                                        <tr>
                                                            <td class="ribbon-main">KARTU PELAJAR</td>
                                                            <td class="ribbon-cut">&nbsp;</td>
                                                        </tr>
                                                    </table>
                                                    <div class="ribbon-gold"></div>
                                                    <table class="main">
                                                        <tr>
                                                            <td class="foto-col">
                                                                @if ($fotoDataUri)
                                                                    <img class="foto" src="{{ $fotoDataUri }}" width="41" height="54" alt="Foto">
                                                                @elseif ($fotoPlaceholderDataUri)
                                                                    <div class="foto-box"><img src="{{ $fotoPlaceholderDataUri }}" width="16" alt=""></div>
                                                                @else
                                                                    <div class="foto-box"></div>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <table class="data">
                                                                    <tr><td class="lbl">NAMA</td><td class="col">:</td><td>{{ mb_strtoupper((string) $dash($kartu['nama'])) }}</td></tr>
                                                                    <tr><td class="lbl">NISN</td><td class="col">:</td><td>{{ $dash($kartu['nisn']) }}</td></tr>
                                                                    <tr><td class="lbl">NIS</td><td class="col">:</td><td>{{ $dash($kartu['nis']) }}</td></tr>
                                                                    <tr><td class="lbl">TTL</td><td class="col">:</td><td>{{ $dash($kartu['ttl']) }}</td></tr>
                                                                    <tr><td class="lbl">JK</td><td class="col">:</td><td>{{ $dash($kartu['jenis_kelamin_label']) }}</td></tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td class="top-qr">
                                                    @if ($qrDataUri)
                                                        <img src="{{ $qrDataUri }}" width="34" height="34" alt="QR">
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                        <table class="alamat-wrap">
                                            <tr>
                                                <td class="lbl">ALAMAT</td>
                                                <td class="col">:</td>
                                                <td class="val-alamat">{{ $dash($kartu['alamat']) }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="cap-row">
                            <table class="cap">
                                <tr>
                                    <td class="cap-txt">Berlaku selama menjadi siswa {{ $namaSingkat }}</td>
                                    <td class="cap-logo">
                                        @if ($logoMadaniDataUri)
                                            <img src="{{ $logoMadaniDataUri }}" height="11" alt="MADANI">
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                </div>
                <div class="ftr-abs">Kartu Pelajar ini dihasilkan oleh sistem resmi {{ $namaSingkat }} dan merupakan dokumen yang sah serta dapat dipergunakan untuk keperluan administrasi akademik maupun nonakademik.</div>
            </div>
        </td>
        <td class="gap"></td>
        <td>
            <div class="card card-back">
                <table class="inner">
                    <tr><td class="bh">IKRAR PELAJAR INDONESIA</td></tr>
                    <tr><td class="bgold">&nbsp;</td></tr>
                    <tr>
                        <td class="bc"@if ($bgBelakangDataUri) style="background-image: url('{{ $bgBelakangDataUri }}'); background-position: center; background-repeat: no-repeat; background-size: cover;"@endif>
                            <div class="bc-lead">Kami Pelajar Indonesia, berikrar untuk:</div>
                            @foreach ($ikrarItems as $i => $item)
                                <div class="bc-item">{{ $i + 1 }}.&nbsp;&nbsp;{{ $item }}</div>
                            @endforeach
                        </td>
                    </tr>
                </table>
                <div class="bf-abs">{{ mb_strtoupper((string) $namaSingkat) }}</div>
            </div>
        </td>
    </tr>
</table>
</body>
</html>
