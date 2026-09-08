<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Pernyataan Peserta Didik — {{ $siswa->nama }}</title>
    @include('siswa.partials.pernyataan-pdf-styles')
</head>
<body>
@php
    $dash = fn ($v) => filled($v) ? $v : '-';
    $jk = match ($siswa->jenis_kelamin) {
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
        default => $siswa->jenis_kelamin,
    };
    $ttl = collect([
        $siswa->tempat_lahir,
        $siswa->tanggal_lahir?->locale('id')->translatedFormat('d F Y'),
    ])->filter()->implode(', ');
@endphp

<div class="surat-wrap">
<div class="surat-title">Surat Pernyataan Peserta Didik</div>

<div class="surat-body">
    <p>Yang bertanda tangan di bawah ini:</p>
    <table class="identitas-surat" width="100%">
        <tr>
            <td class="label" width="38%">Nama</td>
            <td class="colon" width="2%">:</td>
            <td width="60%">{{ $dash($siswa->nama) }}</td>
        </tr>
        <tr>
            <td class="label" width="38%">NISN</td>
            <td class="colon" width="2%">:</td>
            <td width="60%">{{ $dash($siswa->nisn) }}</td>
        </tr>
        <tr>
            <td class="label" width="38%">NIS</td>
            <td class="colon" width="2%">:</td>
            <td width="60%">{{ $dash($siswa->nis) }}</td>
        </tr>
        <tr>
            <td class="label" width="38%">NIK</td>
            <td class="colon" width="2%">:</td>
            <td width="60%">{{ $dash($siswa->nik) }}</td>
        </tr>
        <tr>
            <td class="label" width="38%">Tempat, tanggal lahir</td>
            <td class="colon" width="2%">:</td>
            <td width="60%">{{ $dash($ttl) }}</td>
        </tr>
        <tr>
            <td class="label" width="38%">Jenis Kelamin</td>
            <td class="colon" width="2%">:</td>
            <td width="60%">{{ $dash($jk) }}</td>
        </tr>
        <tr>
            <td class="label" width="38%">Nama orang tua/wali</td>
            <td class="colon" width="2%">:</td>
            <td width="60%">{{ $dash($namaWaliEfektif) }}</td>
        </tr>
    </table>

    <p>Dengan ini menyatakan yang sesungguhnya bahwa sebagai peserta didik di MTsN 11 Majalengka, saya bersedia dan sanggup untuk:</p>
    <ol>
        <li>Mematuhi seluruh peraturan dan tata tertib yang berlaku di madrasah.</li>
        <li>Menjalankan ibadah serta menjaga akhlak mulia.</li>
        <li>Mengikuti seluruh kegiatan pembelajaran dan kegiatan madrasah lainnya dengan penuh tanggung jawab.</li>
        <li>Menjaga nama baik madrasah serta tidak melakukan perbuatan yang dapat merugikan diri sendiri maupun madrasah.</li>
    </ol>
    <p>Apabila di kemudian hari saya melanggar peraturan dan tata tertib yang berlaku di MTsN 11 Majalengka, saya bersedia menerima sanksi sesuai dengan ketentuan yang berlaku, termasuk sanksi pemberhentian sebagai peserta didik.</p>
    <p>Demikian surat pernyataan ini saya buat dengan sebenar-benarnya, dalam keadaan sadar, dan tanpa paksaan dari pihak mana pun untuk dapat dipergunakan sebagaimana mestinya.</p>
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
