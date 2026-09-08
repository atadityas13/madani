<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Pernyataan Biodata — {{ $siswa->nama }}</title>
    @include('siswa.partials.pernyataan-pdf-styles')
</head>
<body>
@php
    $dash = fn ($v) => filled($v) ? $v : '-';
    $namaMadrasah = $madrasah->namaKop();
    $kontakKop = collect([
        $madrasah->telepon ? 'Telp. '.$madrasah->telepon : null,
        $madrasah->email ? 'E-mail: '.$madrasah->email : null,
    ])->filter()->implode(' ');
    $identitasAll = array_merge([['Nama', $siswa->nama]], $identitasRows);
@endphp

<table class="kop">
    <tr>
        <td class="kop-logo">
            @if ($logoDataUri)
                <img src="{{ $logoDataUri }}" alt="Logo">
            @endif
        </td>
        <td class="kop-text">
            <div class="line1">KEMENTERIAN AGAMA REPUBLIK INDONESIA</div>
            <div class="line2">KANTOR KEMENTERIAN AGAMA KABUPATEN MAJALENGKA</div>
            <div class="line3">{{ $namaMadrasah }}</div>
            @if ($kopAlamat !== '')
                <div class="line4">{{ $kopAlamat }}</div>
            @endif
            @if ($kontakKop !== '')
                <div class="line5">{{ $kontakKop }}</div>
            @endif
        </td>
        <td class="kop-qr">
            @if ($qrDataUri)
                <img src="{{ $qrDataUri }}" alt="QR verifikasi">
            @endif
        </td>
    </tr>
</table>
<hr class="kop-line">

<div class="doc-title">Biodata Siswa</div>

<div class="foto-float">
    @if ($fotoDataUri)
        <img src="{{ $fotoDataUri }}" alt="Foto siswa">
    @else
        <table class="foto-placeholder"><tr><td>Foto</td></tr></table>
    @endif
</div>

<div class="section section-first">Data siswa</div>
<table class="rows" width="100%">
    @foreach ($identitasAll as [$label, $value])
        <tr>
            <td class="label" width="38%">{{ strtoupper($label) }}</td>
            <td class="colon" width="2%">:</td>
            <td class="value" width="60%">{{ $dash($value) }}</td>
        </tr>
    @endforeach
</table>
<div class="clear"></div>

<div class="section">Alamat siswa</div>
<table class="rows" width="100%">
    @foreach ($alamatRows as [$label, $value])
        <tr>
            <td class="label" width="38%">{{ strtoupper($label) }}</td>
            <td class="colon" width="2%">:</td>
            <td class="value" width="60%">{{ $dash($value) }}</td>
        </tr>
    @endforeach
</table>

<div class="page-break">
@foreach ([['Ayah kandung', $ayahRows], ['Ibu kandung', $ibuRows], ['Wali', $waliRows]] as [$judulOrtu, $rowsOrtu])
    <div class="ortu-block">
        <div class="section{{ $loop->first ? ' section-first' : '' }}">{{ $judulOrtu }}</div>
        <table class="rows" width="100%">
            @foreach ($rowsOrtu as [$label, $value])
                <tr>
                    <td class="label" width="38%">{{ strtoupper($label) }}</td>
                    <td class="colon" width="2%">:</td>
                    <td class="value" width="60%">{{ $dash($value) }}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endforeach
</div>

<div class="page-break">
<div class="section section-first">Data jenjang sebelumnya</div>
<table class="rows" width="100%">
    @foreach ($jenjangRows as [$label, $value])
        <tr>
            <td class="label" width="38%">{{ strtoupper($label) }}</td>
            <td class="colon" width="2%">:</td>
            <td class="value" width="60%">{{ $dash($value) }}</td>
        </tr>
    @endforeach
</table>

<div class="section">Pernyataan kebenaran data</div>
<div class="pernyataan-box">
    <p>{{ $teksPoin1 }}</p>
    <p class="penutup">{{ $teksPenutupBiodata }}</p>
</div>

@include('siswa.partials.ttd-pernyataan', [
    'kota' => $madrasahKota,
    'tanggalSurat' => $tanggalSurat,
    'namaSiswa' => $siswa->nama,
    'nisn' => $siswa->nisn,
    'namaWali' => $namaWaliEfektif,
    'ttdSiswaDataUri' => $ttdSiswaDataUri,
    'ttdWaliDataUri' => $ttdWaliDataUri,
])
</div>

<div class="footer">
    dokumen digenerate oleh sistem MADANI MTsN 11 Majalengka · {{ $generatedAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
</div>
</body>
</html>
