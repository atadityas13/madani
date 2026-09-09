<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>SPTJM TPG — {{ $namaLengkap }}</title>
    <style>
        @page { margin: 48px 56px 48px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.45;
        }
        .surat-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin: 0 0 18px;
        }
        p { margin: 0 0 8px; text-align: justify; }
        .identitas {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0 12px;
        }
        .identitas td {
            vertical-align: top;
            padding: 2px 0;
        }
        .identitas .label { width: 34%; }
        .identitas .colon { width: 2%; }
        .poin { margin: 0 0 6px 18px; text-align: justify; }
        .poin-sub { margin: 0 0 4px 36px; text-align: justify; }
        .ttd-wrap {
            width: 100%;
            margin-top: 28px;
        }
        .ttd-box {
            width: 46%;
            margin-left: auto;
            text-align: center;
        }
        .ttd-space {
            height: 72px;
            line-height: 72px;
            font-size: 10px;
            color: #444;
        }
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="surat-title">Surat Pernyataan Tanggung Jawab Mutlak</div>

<p>Yang Bertanda tangan di bawah ini :</p>

<table class="identitas">
    <tr>
        <td class="label">Nama</td>
        <td class="colon">:</td>
        <td>{{ $namaLengkap }}</td>
    </tr>
    <tr>
        <td class="label">NUPTK / PegID</td>
        <td class="colon">:</td>
        <td>{{ $nuptk }}</td>
    </tr>
    <tr>
        <td class="label">N R G</td>
        <td class="colon">:</td>
        <td>{{ $nrg }}</td>
    </tr>
    <tr>
        <td class="label">Tempat Tugas</td>
        <td class="colon">:</td>
        <td>{{ $tempatTugas }}</td>
    </tr>
    <tr>
        <td class="label">Alamat Tempat Tugas</td>
        <td class="colon">:</td>
        <td>{{ $alamatTempatTugas }}</td>
    </tr>
</table>

<p>Menyatakan dengan sesungguhnya bahwa :</p>

<p class="poin">1. Saya guru sertifikasi pada Kantor Kementerian Agama Kabupaten Majalengka dan saya tidak terikat sebagai tenaga tetap selain instansi madrasah. Tenaga tetap dimaksud antara lain sbb :</p>
<p class="poin-sub">a. Penyuluh Agama;</p>
<p class="poin-sub">b. Dosen Perguruan Tinggi yang memiliki NIDN atau memiliki NIDN bagi Dokter pendidik klinis penuh waktu atau memiliki NIDK dosen paruh waktu;</p>
<p class="poin-sub">c. Tenaga Pendamping pada Program Pemerintah</p>
<p class="poin-sub" style="margin-left: 54px;">1) Tenaga Kesejahteraaan Sosial Kecamatan (TKSK)</p>
<p class="poin-sub" style="margin-left: 54px;">2) Program Nasional Pemberdayaan Masyarakat (PNPM)</p>
<p class="poin-sub" style="margin-left: 54px;">3) Pemberdayaan Masyarakat Usaha Tani (PMUT)</p>
<p class="poin-sub" style="margin-left: 54px;">4) Pendamping Korban Tindak Kekerasan dan Pekerja Migran (KTKPM)</p>
<p class="poin-sub" style="margin-left: 54px;">5) Pendamping Keluarga Harapan (PKH)</p>
<p class="poin-sub" style="margin-left: 54px;">6) Tenaga Pendamping Desa</p>
<p class="poin-sub" style="margin-left: 54px;">7) Pemberdayaan Masyarakat Pesisir (PMP)</p>
<p class="poin-sub">d. Pegawai Pemerintah dengan Perjanjian Kerja (P3K) atau Pegawai Pemerintah Non Pegawai Negeri (PPNPN) bukan Guru;</p>
<p class="poin-sub">e. Pengurus Komisi Pemilihan Umum (KPU), Badan Pengawas Pemilu (Bawaslu) dan Badan Amil Zakat Nasional (BAZNAS);</p>
<p class="poin-sub">f. Pengurus Partai Politik.</p>

<p class="poin">2. Tidak merangkap jabatan lembaga eksekutif, yudikatif atau legislatif yang meliputi :</p>
<p class="poin-sub">a. Perangkat Desa/Kelurahan, PNS dengan jabatan Non guru/Pengawas dan TNI/Polri;</p>
<p class="poin-sub">b. Anggota Mahkamah Agung, Mahkamah Konstitusi, Komisi Yudisial atau Ombudsman;</p>
<p class="poin-sub">c. Anggota Dewan Perwakilan Rakyat atau Dewan Perwakilan Daerah.</p>

<p class="poin">3. Apabila dikemudian hari ditemukan ketidaksesuaian dengan regulasi yang berlaku dan dengan Surat pernyataan yang saya buat maka saya siap menerima sanksi dan atau Pengembalian dana yang sudah saya terima ke Kas Negara.</p>

<p>Demikian pernyataan ini kami buat dengan sebenar-benar dan tanpa paksaan dari pihak manapun.</p>

<div class="ttd-wrap">
    <div class="ttd-box">
        <div>{{ $kotaTtd }}, {{ $tanggalSurat }}</div>
        <div>Yang Membuat Pernyataan,</div>
        <div class="ttd-space">Materai 10.000</div>
        <div class="ttd-nama">{{ $namaLengkap }}</div>
    </div>
</div>
</body>
</html>
