<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu E-Pelajar — {{ $siswa->nama }}</title>
    <style>
        @page { margin: 40pt 28pt; }
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

        /* Outer frame — border on wrapper, padding inside avoids clip */
        table.frame {
            width: 242.6pt;
            border: 1.15pt solid #022C22;
            border-collapse: separate;
            border-spacing: 0;
            background: #ffffff;
        }
        table.frame > tbody > tr > td { padding: 0; }

        table.inner { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.inner td { padding: 0; margin: 0; }

        td.hdr {
            background: #022C22;
            padding: 0 !important;
        }
        table.kop { width: 100%; border-collapse: collapse; }
        td.kop-pad { height: 8pt; background: #022C22; font-size: 1pt; line-height: 1pt; }
        td.kop-logo {
            width: 36pt;
            background: #022C22;
            text-align: center;
            vertical-align: middle !important;
            padding: 2pt 6pt !important;
        }
        td.kop-logo img { width: 16pt; height: 16pt; }
        td.kop-text {
            background: #022C22;
            text-align: center;
            vertical-align: middle !important;
            padding: 2pt 3pt !important;
            line-height: 1.12;
        }
        .kop-l1, .kop-l2 {
            color: #FBBF24;
            font-size: 3.4pt;
            font-weight: bold;
            white-space: nowrap;
        }
        .kop-l3 {
            color: #FBBF24;
            font-size: 4.1pt;
            font-weight: bold;
            margin-top: 0.5pt;
            white-space: nowrap;
        }
        .kop-l4, .kop-l5 {
            color: #ffffff;
            font-size: 2.8pt;
            margin-top: 0.4pt;
            white-space: nowrap;
        }

        td.bdy {
            background: #ffffff;
            padding: 4pt 11pt 5pt 7pt !important;
            vertical-align: top !important;
        }

        table.ribbon-wrap { border-collapse: collapse; }
        td.ribbon-cell {
            background: #065F46;
            color: #ffffff;
            font-size: 5.4pt;
            font-weight: bold;
            letter-spacing: 0.7pt;
            padding: 2pt 12pt 2pt 5pt !important;
            white-space: nowrap;
        }
        .ribbon-gold {
            width: 56pt;
            height: 2pt;
            background: #F59E0B;
            margin: 1.5pt 0 3pt 0;
        }

        table.main { width: 100%; border-collapse: collapse; }
        table.main > tbody > tr > td { vertical-align: top; padding: 0; }
        td.foto-col { width: 40pt; padding-right: 5pt !important; }
        img.foto, .foto-box {
            width: 36pt;
            height: 48pt;
            border: 0.9pt solid #065F46;
            display: block;
        }
        .foto-box { background: #f8fafc; text-align: center; }
        .foto-box img { width: 16pt; margin-top: 13pt; }

        td.data-col { width: 125pt; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data td {
            padding: 0.35pt 0;
            font-size: 5.2pt;
            line-height: 1.14;
            vertical-align: top;
            font-weight: bold;
            color: #020617;
        }
        table.data tr:not(:last-child) td {
            white-space: nowrap;
        }
        td.lbl { width: 26pt; text-transform: uppercase; }
        td.col { width: 6pt; }

        td.qr-col {
            width: 36pt;
            text-align: center;
            vertical-align: top !important;
            padding: 1pt 8pt 0 2pt !important;
        }
        td.qr-col img {
            width: 24pt;
            height: 24pt;
            border: 0.5pt solid #D1FAE5;
            padding: 1.5pt;
            background: #ffffff;
        }

        table.cap { width: 100%; border-collapse: collapse; margin-top: 4pt; }
        table.cap td { vertical-align: middle !important; padding: 0 !important; }
        td.cap-txt { font-size: 4.1pt; font-weight: bold; color: #020617; }
        td.cap-logo { width: 50pt; text-align: right; }
        td.cap-logo img { height: 9pt; width: auto; }

        td.ftr {
            background: #022C22;
            color: #ffffff;
            text-align: center;
            font-size: 2.15pt;
            line-height: 1.15;
            padding: 3.5pt 3pt !important;
            white-space: nowrap;
        }

        /* Belakang */
        table.frame-back {
            width: 242.6pt;
            border: 1.15pt solid #022C22;
            border-collapse: separate;
            border-spacing: 0;
            background: #ffffff;
        }
        table.frame-back > tbody > tr > td { padding: 0; }
        td.bh {
            background: #022C22;
            color: #FBBF24;
            text-align: center;
            font-size: 7.2pt;
            font-weight: bold;
            letter-spacing: 1pt;
            padding: 5pt 6pt !important;
        }
        td.bgold { height: 2pt; background: #F59E0B; font-size: 1pt; line-height: 1pt; }
        td.bc {
            height: 108pt;
            text-align: center;
            vertical-align: middle !important;
            padding: 6pt 10pt !important;
            background-color: #f3f4f6;
        }
        /* Teks ikrar langsung di atas wash (mirror Ta'lim: tanpa kotak putih solid) */
        .bc-lead {
            color: #022C22;
            font-size: 5.8pt;
            font-weight: bold;
            margin-bottom: 5pt;
        }
        .bc-item {
            color: #020617;
            font-size: 6.2pt;
            font-weight: bold;
            margin: 3pt 0;
            white-space: nowrap;
        }
        td.bf {
            background: #022C22;
            color: #ffffff;
            text-align: center;
            font-size: 4.0pt;
            font-weight: bold;
            letter-spacing: 0.5pt;
            padding: 4pt 6pt !important;
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
            <table class="frame">
                <tr>
                    <td>
                        <table class="inner">
                            <tr>
                                <td class="hdr">
                                    <table class="kop">
                                        <tr><td class="kop-pad" colspan="3">&nbsp;</td></tr>
                                        <tr>
                                            <td class="kop-logo">
                                                @if ($logoKemenagDataUri)
                                                    <img src="{{ $logoKemenagDataUri }}" width="16" height="16" alt="Kemenag">
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
                                                    <img src="{{ $logoDataUri }}" width="16" height="16" alt="Madrasah">
                                                @endif
                                            </td>
                                        </tr>
                                        <tr><td class="kop-pad" colspan="3">&nbsp;</td></tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="bdy">
                                    <table class="ribbon-wrap"><tr><td class="ribbon-cell">KARTU PELAJAR</td></tr></table>
                                    <div class="ribbon-gold"></div>
                                    <table class="main">
                                        <tr>
                                            <td class="foto-col">
                                                @if ($fotoDataUri)
                                                    <img class="foto" src="{{ $fotoDataUri }}" width="36" height="48" alt="Foto">
                                                @elseif ($fotoPlaceholderDataUri)
                                                    <div class="foto-box"><img src="{{ $fotoPlaceholderDataUri }}" width="16" alt=""></div>
                                                @else
                                                    <div class="foto-box"></div>
                                                @endif
                                            </td>
                                            <td class="data-col">
                                                <table class="data">
                                                    <tr><td class="lbl">NAMA</td><td class="col">:</td><td>{{ mb_strtoupper((string) $dash($kartu['nama'])) }}</td></tr>
                                                    <tr><td class="lbl">NISN</td><td class="col">:</td><td>{{ $dash($kartu['nisn']) }}</td></tr>
                                                    <tr><td class="lbl">NIS</td><td class="col">:</td><td>{{ $dash($kartu['nis']) }}</td></tr>
                                                    <tr><td class="lbl">TTL</td><td class="col">:</td><td>{{ $dash($kartu['ttl']) }}</td></tr>
                                                    <tr><td class="lbl">JK</td><td class="col">:</td><td>{{ $dash($kartu['jenis_kelamin_label']) }}</td></tr>
                                                    <tr><td class="lbl">ALAMAT</td><td class="col">:</td><td>{{ $dash($kartu['alamat']) }}</td></tr>
                                                </table>
                                            </td>
                                            <td class="qr-col">
                                                    @if ($qrDataUri)
                                                    <img src="{{ $qrDataUri }}" width="24" height="24" alt="QR">
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
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
                                <td class="ftr">Kartu Pelajar ini dihasilkan oleh sistem resmi {{ $namaSingkat }} dan merupakan dokumen yang sah serta dapat dipergunakan untuk keperluan administrasi akademik maupun nonakademik.</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
        <td class="gap"></td>
        <td>
            <table class="frame-back">
                <tr>
                    <td>
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
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
