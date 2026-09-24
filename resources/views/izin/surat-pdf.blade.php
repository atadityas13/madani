<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Izin — {{ $namaSiswa }}</title>
    <style>
        @page { margin: 24mm 20mm 20mm 20mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            color: #111;
            line-height: 1.45;
        }
        .tanggal { text-align: right; margin-bottom: 18px; }
        .meta { margin-bottom: 16px; }
        .meta table { border-collapse: collapse; }
        .meta td { padding: 1px 0; vertical-align: top; }
        .meta .k { width: 72px; }
        .meta .c { width: 14px; }
        .meta .v { font-weight: bold; }
        .alamat { margin: 14px 0 16px; }
        .identitas { margin: 8px 0 12px 12px; }
        .identitas table { border-collapse: collapse; }
        .identitas td { padding: 1px 0; vertical-align: top; }
        .identitas .k { width: 56px; }
        .identitas .c { width: 14px; }
        .paragraf { text-align: justify; margin: 10px 0; }
        .ttd-wrap { width: 42%; margin-left: auto; margin-top: 28px; text-align: center; }
        .ttd-img { height: 64px; margin: 8px 0; }
        .ttd-nama { font-weight: bold; margin-top: 4px; }
        .ttd-placeholder { font-style: italic; color: #555; margin: 18px 0; }
    </style>
</head>
<body>
    <div class="tanggal">{{ $kota }}, {{ $tanggalSurat }}</div>

    <div class="meta">
        <table>
            <tr>
                <td class="k">Hal</td>
                <td class="c">:</td>
                <td class="v">{{ $hal }}</td>
            </tr>
            <tr>
                <td class="k">Lampiran</td>
                <td class="c">:</td>
                <td>{{ $lampiranLabel }}</td>
            </tr>
        </table>
    </div>

    <div class="alamat">
        {{ $kepadaYth }}<br>
        {{ $waliKelas }}<br>
        {{ $madrasah }}<br>
        {{ $diTempat }}
    </div>

    <div>{{ $salamPembuka }}</div>
    <div style="margin-top: 8px;">{{ $pengantar }}</div>

    <div class="identitas">
        <table>
            <tr>
                <td class="k">Nama</td>
                <td class="c">:</td>
                <td><strong>{{ $namaSiswa }}</strong></td>
            </tr>
            <tr>
                <td class="k">Kelas</td>
                <td class="c">:</td>
                <td>{{ $kelasSiswa }}</td>
            </tr>
        </table>
    </div>

    <div class="paragraf">{{ $paragraf }}</div>
    <div class="paragraf">{{ $penutup }}</div>
    <div>{{ $salamPenutup }}</div>

    <div class="ttd-wrap">
        <div>{{ $hormatKami }}</div>
        <div>{{ $peranPenandatangan }}</div>
        @if ($ttdWaliDataUri)
            <div><img class="ttd-img" src="{{ $ttdWaliDataUri }}" alt="TTD"></div>
        @else
            <div class="ttd-placeholder">(Tanda Tangan)</div>
        @endif
        <div class="ttd-nama">{{ $namaWali }}</div>
    </div>
</body>
</html>
