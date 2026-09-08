<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu E-Pelajar — {{ $siswa->nama }}</title>
    <style>
        /* ISO/IEC 7810 ID-1: 85.60 × 53.98 mm */
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

        /* ===== DEPAN: hdr 34 + bdy 92 + cap 10 + ftr 14.8 = 150.8 ===== */
        td.hdr {
            background: #022C22;
            height: 34pt;
            padding: 0 !important;
        }
        table.kop { width: 100%; border-collapse: collapse; }
        td.kop-logo {
            width: 32pt;
            background: #022C22;
            text-align: center;
            vertical-align: middle !important;
            padding: 0.5pt 0.6pt !important;
        }
        td.kop-logo img { width: 28pt; height: 28pt; }
        td.kop-text {
            background: #022C22;
            text-align: center;
            vertical-align: middle !important;
            padding: 1pt 0.5pt !important;
            line-height: 1.08;
        }
        .kop-l1, .kop-l2 {
            color: #FBBF24;
            font-size: 4.0pt;
            font-weight: bold;
            white-space: nowrap;
        }
        .kop-l3 {
            color: #FBBF24;
            font-size: 4.8pt;
            font-weight: bold;
            margin-top: 0.25pt;
            white-space: nowrap;
        }
        .kop-l4, .kop-l5 {
            color: #ffffff;
            font-size: 3.15pt;
            margin-top: 0.2pt;
            white-space: nowrap;
        }

        td.bdy {
            background: #ffffff;
            height: 92pt;
            padding: 2pt 1.5pt 1pt 3pt !important;
            vertical-align: top !important;
        }

        table.top-row { width: 100%; border-collapse: collapse; }
        table.top-row > tbody > tr > td { vertical-align: top; padding: 0; }
        td.top-left { padding-right: 2pt !important; }
        td.top-qr {
            width: 34pt;
            text-align: right;
            vertical-align: top !important;
            padding: 0 !important;
        }
        td.top-qr img {
            width: 32pt;
            height: 32pt;
            border: 0.45pt solid #D1FAE5;
            padding: 0.7pt;
            background: #ffffff;
            display: block;
        }

        /* Badge + garis emas sebagai baris tabel (hindari border DomPDF) */
        table.ribbon { border-collapse: collapse; width: auto; }
        td.ribbon-main {
            background: #065F46;
            color: #ffffff;
            font-size: 4.9pt;
            font-weight: bold;
            letter-spacing: 0.85pt;
            padding: 1.7pt 6pt !important;
            white-space: nowrap;
            vertical-align: middle !important;
            border: none !important;
        }
        td.ribbon-gold-row {
            background: #F59E0B;
            height: 1.6pt;
            font-size: 1pt;
            line-height: 1pt;
            padding: 0 !important;
            border: none !important;
        }
        .ribbon-gap { height: 1.8pt; font-size: 1pt; line-height: 1pt; }

        table.main { width: 100%; border-collapse: collapse; }
        table.main > tbody > tr > td { vertical-align: top; padding: 0; }
        td.foto-col { width: 40pt; padding-right: 2.5pt !important; }
        img.foto, .foto-box {
            width: 37pt;
            height: 49pt;
            border: 0.8pt solid #065F46;
            display: block;
        }
        .foto-box { background: #f8fafc; text-align: center; }
        .foto-box img { width: 14pt; margin-top: 14pt; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data td {
            padding: 0.28pt 0;
            font-size: 4.5pt;
            line-height: 1.28;
            letter-spacing: 0.1pt;
            vertical-align: top;
            font-weight: bold;
            color: #020617;
        }
        table.data tr:not(:last-child) td { white-space: nowrap; }
        td.lbl { width: 21pt; text-transform: uppercase; }
        td.col { width: 4pt; }
        td.val-alamat {
            font-size: 4.15pt;
            line-height: 1.28;
            letter-spacing: 0.1pt;
            white-space: normal;
        }

        td.cap-row {
            background: #ffffff;
            height: 10pt;
            padding: 0 4pt 0.5pt 3pt !important;
            vertical-align: bottom !important;
        }
        table.cap { width: 100%; border-collapse: collapse; }
        table.cap td { vertical-align: middle !important; padding: 0 !important; }
        td.cap-txt { font-size: 3.15pt; font-weight: bold; color: #020617; white-space: nowrap; }
        td.cap-logo { width: 50pt; text-align: right; padding-right: 3.5pt !important; }
        td.cap-logo img { height: 9pt; width: auto; display: block; margin-left: auto; }

        /* Footer depan: baseline DomPDF → top + padding terukur */
        td.ftr {
            background: #022C22;
            height: 14.8pt;
            padding: 0 !important;
            vertical-align: top !important;
        }
        table.ftr-tbl { width: 100%; border-collapse: collapse; }
        td.ftr-cell {
            background: #022C22;
            height: 14.8pt;
            padding: 4.0pt 2pt 0 2pt !important;
            vertical-align: top !important;
            text-align: center;
            color: #ffffff;
            font-size: 2.35pt;
            line-height: 2.35pt;
            white-space: nowrap;
            letter-spacing: -0.02pt;
        }

        /* ===== BELAKANG: bh 18 + gold 1.8 + bc 111 + bf 20 = 150.8 ===== */
        td.bh {
            background: #022C22;
            color: #FBBF24;
            text-align: center;
            height: 18pt;
            font-size: 6.5pt;
            font-weight: bold;
            letter-spacing: 0.85pt;
            padding: 0 4pt !important;
            vertical-align: middle !important;
        }
        td.bgold { height: 1.8pt; background: #F59E0B; font-size: 1pt; line-height: 1pt; }
        td.bc {
            height: 111pt;
            text-align: center;
            vertical-align: middle !important;
            padding: 3pt 8pt !important;
            background-color: #f3f4f6;
        }
        .bc-lead {
            color: #022C22;
            font-size: 5.1pt;
            font-weight: bold;
            margin-bottom: 2.5pt;
        }
        .bc-item {
            color: #020617;
            font-size: 5.4pt;
            font-weight: bold;
            margin: 1.6pt 0;
            white-space: nowrap;
        }
        td.bf {
            background: #022C22;
            height: 20pt;
            padding: 0 !important;
            vertical-align: top !important;
        }
        table.bf-tbl { width: 100%; border-collapse: collapse; }
        td.bf-cell {
            background: #022C22;
            height: 20pt;
            padding: 3.8pt 4pt 0 4pt !important;
            vertical-align: top !important;
            text-align: center;
            color: #ffffff;
            font-size: 5.6pt;
            font-weight: bold;
            letter-spacing: 0.35pt;
            white-space: nowrap;
            line-height: 5.6pt;
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
                                            <img src="{{ $logoKemenagDataUri }}" width="28" height="28" alt="Kemenag">
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
                                            <img src="{{ $logoDataUri }}" width="28" height="28" alt="Madrasah">
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="bdy">
                            <table class="top-row">
                                <tr>
                                    <td class="top-left">
                                        <table class="ribbon">
                                            <tr>
                                                <td class="ribbon-main">KARTU PELAJAR</td>
                                            </tr>
                                            <tr>
                                                <td class="ribbon-gold-row">&nbsp;</td>
                                            </tr>
                                        </table>
                                        <div class="ribbon-gap">&nbsp;</div>
                                        <table class="main">
                                            <tr>
                                                <td class="foto-col">
                                                    @if ($fotoDataUri)
                                                        <img class="foto" src="{{ $fotoDataUri }}" width="37" height="49" alt="Foto">
                                                    @elseif ($fotoPlaceholderDataUri)
                                                        <div class="foto-box"><img src="{{ $fotoPlaceholderDataUri }}" width="14" alt=""></div>
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
                                                        <tr><td class="lbl">ALAMAT</td><td class="col">:</td><td class="val-alamat">{{ $dash($kartu['alamat']) }}</td></tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="top-qr">
                                        @if ($qrDataUri)
                                            <img src="{{ $qrDataUri }}" width="32" height="32" alt="QR">
                                        @endif
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
                                            <img src="{{ $logoMadaniDataUri }}" height="9" alt="MADANI">
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="ftr">
                            <table class="ftr-tbl"><tr>
                                <td class="ftr-cell">Kartu Pelajar ini dihasilkan oleh sistem resmi {{ $namaSingkat }} dan merupakan dokumen yang sah serta dapat dipergunakan untuk keperluan administrasi akademik maupun nonakademik.</td>
                            </tr></table>
                        </td>
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
                    <tr>
                        <td class="bf">
                            <table class="bf-tbl"><tr>
                                <td class="bf-cell">Madrasah Maju, Bermutu, Mendunia.</td>
                            </tr></table>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>
</body>
</html>
