<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu E-Pelajar — {{ $siswa->nama }}</title>
    <style>
        @page { margin: 36pt 24pt; }
        @include('siswa.partials.kartu-e-pelajar-styles')
    </style>
</head>
<body>
@php
    $dash = fn ($v) => filled($v) ? $v : '—';
@endphp
<table class="sheet">
    <tr>
        <td>
            @include('siswa.partials.kartu-e-pelajar-depan')
        </td>
        <td class="gap"></td>
        <td>
            @include('siswa.partials.kartu-e-pelajar-belakang')
        </td>
    </tr>
</table>
</body>
</html>
