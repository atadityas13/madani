<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Lampiran bukti</title>
    <style>
        @page { margin: 12mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            color: #111;
            text-align: center;
        }
        .judul {
            font-weight: bold;
            margin-bottom: 6px;
        }
        .jenis {
            margin-bottom: 14px;
            color: #333;
        }
        .bukti {
            max-width: 100%;
            max-height: 240mm;
        }
    </style>
</head>
<body>
    <div class="judul">Lampiran Bukti Ketidakhadiran</div>
    @if ($jenisBukti)
        <div class="jenis">{{ $jenisBukti }}</div>
    @endif
    <img class="bukti" src="{{ $imageDataUri }}" alt="Lampiran">
</body>
</html>
