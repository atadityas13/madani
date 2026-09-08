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

        /* Kop ~25% */
        td.hdr {
            background: #022C22;
            height: 38pt;
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
        td.kop-logo img { width: 31pt; height: 31pt; }
        td.kop-text {
            background: #022C22;
            text-align: center;
            vertical-align: middle !important;
            padding: 1.5pt 0.5pt !important;
            line-height: 1.08;
        }
        .kop-l1, .kop-l2 {
            color: #FBBF24;
            font-size: 4.15pt;
            font-weight: bold;
            white-space: nowrap;
        }
        .kop-l3 {
            color: #FBBF24;
            font-size: 5.0pt;
            font-weight: bold;
            margin-top: 0.35pt;
            white-space: nowrap;
        }
        .kop-l4, .kop-l5 {
            color: #ffffff;
            font-size: 3.35pt;
            margin-top: 0.3pt;
            white-space: nowrap;
        }

        td.bdy {
            background: #ffffff;
            height: 102pt;
            padding: 2.5pt 1.5pt 0.8pt 3pt !important;
            vertical-align: top !important;
        }
        table.body-fill {
            width: 100%;
            border-collapse: collapse;
        }
        table.body-fill > tbody > tr > td { padding: 0; }
        td.body-main { vertical-align: top !important; }
        td.body-spacer { height: 18pt; font-size: 1pt; line-height: 1pt; }
        td.body-cap {
            height: 9pt;
            vertical-align: bottom !important;
            padding: 0 !important;
        }

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

        table.ribbon-wrap { border-collapse: collapse; }
        td.ribbon-cell {
            background: #065F46;
            color: #ffffff;
            font-size: 5.0pt;
            font-weight: bold;
            letter-spacing: 0.55pt;
            padding: 1.6pt 9pt 1.6pt 4pt !important;
            white-space: nowrap;
        }
        .ribbon-gold {
            width: 50pt;
            height: 1.7pt;
            background: #F59E0B;
            margin: 1pt 0 2.2pt 0;
        }

        table.main { width: 100%; border-collapse: collapse; }
        table.main > tbody > tr > td { vertical-align: top; padding: 0; }
        td.foto-col { width: 30pt; padding-right: 3.5pt !important; }
        img.foto, .foto-box {
            width: 27pt;
            height: 36pt;
            border: 0.8pt solid #065F46;
            display: block;
        }
        .foto-box { background: #f8fafc; text-align: center; }
        .foto-box img { width: 12pt; margin-top: 10pt; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data td {
            padding: 0.2pt 0;
            font-size: 4.85pt;
            line-height: 1.1;
            vertical-align: top;
            font-weight: bold;
            color: #020617;
        }
        table.data tr:not(:last-child) td { white-space: nowrap; }
        td.lbl { width: 22pt; text-transform: uppercase; }
        td.col { width: 4.5pt; }

        table.cap { width: 100%; border-collapse: collapse; }
        table.cap td { vertical-align: middle !important; padding: 0 !important; }
        td.cap-txt { font-size: 3.35pt; font-weight: bold; color: #020617; white-space: nowrap; }
        td.cap-logo { width: 40pt; text-align: right; }
        td.cap-logo img { height: 7pt; width: auto; }

        td.ftr {
            background: #022C22;
            color: #ffffff;
            text-align: center;
            height: 11pt;
            font-size: 2.15pt;
            line-height: 1.1;
            padding: 1.8pt 2pt !important;
            white-space: nowrap;
            vertical-align: middle !important;
        }

        /* Belakang */
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
            height: 118.2pt;
            text-align: center;
            vertical-align: middle !important;
            padding: 4pt 8pt !important;
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
        td.bf {
            background: #022C22;
            color: #ffffff;
            text-align: center;
            height: 11pt;
            font-size: 3.7pt;
            font-weight: bold;
            letter-spacing: 0.45pt;
            padding: 2pt 4pt !important;
            white-space: nowrap;
            vertical-align: middle !important;
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
                <table class="inner">
                    <tr>
                        <td class="hdr">
                            <table class="kop">
                                <tr>
                                    <td class="kop-logo">
                                        @if ($logoKemenagDataUri)
                                            <img src="{{ $logoKemenagDataUri }}" width="31" height="31" alt="Kemenag">
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
                                            <img src="{{ $logoDataUri }}" width="31" height="31" alt="Madrasah">
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
                                                    <table class="ribbon-wrap"><tr><td class="ribbon-cell">KARTU PELAJAR</td></tr></table>
                                                </td>
                                                <td class="top-qr" rowspan="3">
                                                    @if ($qrDataUri)
                                                        <img src="{{ $qrDataUri }}" width="34" height="34" alt="QR">
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="top-left">
                                                    <div class="ribbon-gold"></div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="top-left">
                                                    <table class="main">
                                                        <tr>
                                                            <td class="foto-col">
                                                                @if ($fotoDataUri)
                                                                    <img class="foto" src="{{ $fotoDataUri }}" width="27" height="36" alt="Foto">
                                                                @elseif ($fotoPlaceholderDataUri)
                                                                    <div class="foto-box"><img src="{{ $fotoPlaceholderDataUri }}" width="12" alt=""></div>
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
                                                                    <tr><td class="lbl">ALAMAT</td><td class="col">:</td><td>{{ $dash($kartu['alamat']) }}</td></tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="body-spacer">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td class="body-cap">
                                        <table class="cap">
                                            <tr>
                                                <td class="cap-txt">Berlaku selama menjadi siswa {{ $namaSingkat }}</td>
                                                <td class="cap-logo">
                                                    @if ($logoMadaniDataUri)
                                                        <img src="{{ $logoMadaniDataUri }}" height="7" alt="MADANI">
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="ftr">Kartu Pelajar ini dihasilkan oleh sistem resmi {{ $namaSingkat }} dan merupakan dokumen yang sah serta dapat dipergunakan untuk keperluan administrasi akademik maupun nonakademik.</td>
                    </tr>
                </table>
            </div>
        </td>
        <td class="gap"></td>
        <td>
            <div class="card">
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
                    <tr><td class="bf">{{ mb_strtoupper((string) $namaSingkat) }}</td></tr>
                </table>
            </div>
        </td>
    </tr>
</table>
</body>
</html>
