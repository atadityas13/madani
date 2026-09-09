<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>SPTJM TPG</title>
    <style>
        @page { margin: 2cm 2.2cm 2cm 2.2cm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #000;
            line-height: 1.28;
            margin: 0;
        }
        .page {
            page-break-after: always;
        }
        .page:last-child {
            page-break-after: auto;
        }
        .surat-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 14px;
            letter-spacing: 0.2px;
        }
        p {
            margin: 0 0 7px;
            text-align: justify;
        }
        .intro, .penutup {
            text-align: left;
        }
        .identitas {
            width: 100%;
            border-collapse: collapse;
            margin: 2px 0 10px;
        }
        .identitas td {
            vertical-align: top;
            padding: 1px 0;
            text-align: left;
        }
        .identitas .label { width: 32%; }
        .identitas .colon { width: 2%; text-align: left; }
        .identitas .value { width: 66%; }
        .poin {
            margin: 0 0 5px;
            padding-left: 18px;
            text-indent: -18px;
            text-align: justify;
        }
        .poin-sub {
            margin: 0 0 3px 18px;
            padding-left: 18px;
            text-indent: -18px;
            text-align: justify;
        }
        .poin-bullet {
            margin: 0 0 2px 36px;
            padding-left: 12px;
            text-indent: -12px;
            text-align: justify;
        }
        .ttd-wrap {
            width: 100%;
            margin-top: 18px;
        }
        .ttd-box {
            width: 42%;
            margin-left: auto;
            text-align: left;
        }
        .ttd-box .kota-tanggal,
        .ttd-box .peran {
            text-align: left;
            margin: 0;
        }
        .materai-area {
            height: 68px;
            text-align: center;
            vertical-align: middle;
            line-height: 68px;
            font-size: 9.5pt;
        }
        .ttd-nama {
            text-align: left;
            font-weight: normal;
            text-decoration: none;
            margin: 0;
        }
    </style>
</head>
<body>
@foreach ($halaman as $data)
<div class="page">
    <div class="surat-title">Surat Pernyataan Tanggung Jawab Mutlak</div>

    <p class="intro">Yang Bertanda tangan di bawah ini :</p>

    <table class="identitas">
        <tr>
            <td class="label">Nama</td>
            <td class="colon">:</td>
            <td class="value">{{ $data['namaLengkap'] }}</td>
        </tr>
        <tr>
            <td class="label">NUPTK / PegID</td>
            <td class="colon">:</td>
            <td class="value">{{ $data['nuptk'] }}</td>
        </tr>
        <tr>
            <td class="label">N R G</td>
            <td class="colon">:</td>
            <td class="value">{{ $data['nrg'] }}</td>
        </tr>
        <tr>
            <td class="label">Tempat Tugas</td>
            <td class="colon">:</td>
            <td class="value">{{ $data['tempatTugas'] }}</td>
        </tr>
        <tr>
            <td class="label">Alamat Tempat Tugas</td>
            <td class="colon">:</td>
            <td class="value">{{ $data['alamatTempatTugas'] }}</td>
        </tr>
    </table>

    <p class="intro">Menyatakan dengan sesungguhnya bahwa :</p>

    <p class="poin">1. Saya guru sertifikasi pada Kantor Kementerian Agama Kabupaten Majalengka dan saya tidak terikat sebagai tenaga tetap selain instansi madrasah. Tenaga tetap dimaksud antara lain sbb :</p>
    <p class="poin-sub">a. Penyuluh Agama;</p>
    <p class="poin-sub">b. Dosen Perguruan Tinggi yang memiliki NIDN atau memiliki NIDN bagi Dokter pendidik klinis penuh waktu atau memiliki NIDK dosen paruh waktu;</p>
    <p class="poin-sub">c. Tenaga Pendamping pada Program Pemerintah</p>
    <p class="poin-bullet">- Tenaga Kesejahteraaan Sosial Kecamatan (TKSK)</p>
    <p class="poin-bullet">- Program Nasional Pemberdayaan Masyarakat (PNPM)</p>
    <p class="poin-bullet">- Pemberdayaan Masyarakat Usaha Tani (PMUT)</p>
    <p class="poin-bullet">- Pendamping Korban Tindak Kekerasan dan Pekerja Migran (KTKPM)</p>
    <p class="poin-bullet">- Pendamping Keluarga Harapan (PKH)</p>
    <p class="poin-bullet">- Tenaga Pendamping Desa</p>
    <p class="poin-bullet">- Pemberdayaan Masyarakat Pesisir (PMP)</p>
    <p class="poin-sub">d. Pegawai Pemerintah dengan Perjanjian Kerja (P3K) atau Pegawai Pemerintah Non Pegawai Negeri (PPNPN) bukan Guru;</p>
    <p class="poin-sub">e. Pengurus Komisi Pemilihan Umum (KPU), Badan Pengawas Pemilu (Bawaslu) dan Badan Amil Zakat Nasional (BAZNAS);</p>
    <p class="poin-sub">f. Pengurus Partai Politik.</p>

    <p class="poin">2. Tidak merangkap jabatan lembaga eksekutif, yudikatif atau legislatif yang meliputi :</p>
    <p class="poin-sub">a. Perangkat Desa/Kelurahan, PNS dengan jabatan Non guru/Pengawas dan TNI/Polri;</p>
    <p class="poin-sub">b. Anggota Mahkamah Agung, Mahkamah Konstitusi, Komisi Yudisial atau Ombudsman;</p>
    <p class="poin-sub">c. Anggota Dewan Perwakilan Rakyat atau Dewan Perwakilan Daerah.</p>

    <p class="poin">3. Apabila dikemudian hari ditemukan ketidaksesuaian dengan regulasi yang berlaku dan dengan Surat pernyataan yang saya buat maka saya siap menerima sanksi dan atau Pengembalian dana yang sudah saya terima ke Kas Negara.</p>

    <p class="penutup">Demikian pernyataan ini kami buat dengan sebenar-benar dan tanpa paksaan dari pihak manapun.</p>

    <div class="ttd-wrap">
        <div class="ttd-box">
            <p class="kota-tanggal">{{ $data['kotaTtd'] }}, {{ $data['tanggalSurat'] }}</p>
            <p class="peran">Yang Membuat Pernyataan,</p>
            <div class="materai-area">Materai 10.000</div>
            <p class="ttd-nama">{{ $data['namaLengkap'] }}</p>
        </div>
    </div>
</div>
@endforeach
</body>
</html>
