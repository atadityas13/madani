<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>SPTJM TPG</title>
    <style>
        @page { margin: 1.7cm 2cm 1.6cm 2cm; }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.22;
            margin: 0;
        }

        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }

        .surat-title {
            width: 100%;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 10px;
            letter-spacing: 0.2px;
        }

        .block { margin: 0 0 6px; }

        .identitas {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 8px;
        }
        .identitas td {
            vertical-align: top;
            padding: 2px 0;
        }
        .identitas .label { width: 35%; }
        .identitas .colon { width: 2%; }
        .identitas .value { width: 63%; }

        .point-intro {
            margin: 0 0 6px;
            text-align: justify;
        }

        .line {
            margin: 0 0 3px;
            text-align: left;
        }

        .prefix {
            display: inline-block;
            width: 22px;
            text-align: left;
        }

        .dash-line {
            margin: 0 0 2px 18px;
            text-align: left;
        }

        .ttd-layout {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .ttd-box {
            width: 46%;
            text-align: left;
        }

        .ttd-date { margin: 0 0 4px; }
        .ttd-peran { margin: 0 0 4px; }

        .ttd-materai {
            height: 42px;
            line-height: 42px;
            text-align: center;
            margin: 0 0 4px;
        }

        .ttd-line {
            margin: 0 0 4px;
            font-size: 10px;
        }

        .ttd-nama {
            margin: 0;
            text-decoration: none;
            font-weight: normal;
        }
    </style>
</head>
<body>
@foreach ($halaman as $data)
<div class="page">
    <div class="surat-title">SURAT PERNYATAAN TANGGUNG JAWAB MUTLAK</div>

    <div class="block">Yang Bertanda tangan di bawah ini :</div>

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

    <div class="block">Menyatakan dengan sesungguhnya bahwa :</div>

    <div class="point-intro">
        1. Saya guru sertifikasi pada Kantor Kementerian Agama Kabupaten Majalengka dan saya tidak terikat sebagai tenaga tetap selain instansi madrasah.
        Tenaga tetap dimaksud antara lain sbb :
    </div>

    <div class="line"><span class="prefix">a.</span>Penyuluh Agama;</div>
    <div class="line"><span class="prefix">b.</span>Dosen Perguruan Tinggi yang memiliki NIDN atau memiliki NIDN bagi Dokter pendidik klinis penuh waktu atau memiliki NIDK dosen paruh waktu;</div>
    <div class="line"><span class="prefix">c.</span>Tenaga Pendamping pada Program Pemerintah</div>

    <div class="dash-line">- Tenaga Kesejahteraaan Sosial Kecamatan (TKSK)</div>
    <div class="dash-line">- Program Nasional Pemberdayaan Masyarakat (PNPM)</div>
    <div class="dash-line">- Pemberdayaan Masyarakat Usaha Tani (PMUT)</div>
    <div class="dash-line">- Pendamping Korban Tindak Kekerasan dan Pekerja Migran (KTKPM)</div>
    <div class="dash-line">- Pendamping Keluarga Harapan (PKH)</div>
    <div class="dash-line">- Tenaga Pendamping Desa</div>
    <div class="dash-line">- Pemberdayaan Masyarakat Pesisir (PMP)</div>

    <div class="line"><span class="prefix">d.</span>Pegawai Pemerintah dengan Perjanjian Kerja (P3K) atau Pegawai Pemerintah Non Pegawai Negeri (PPNPN) bukan Guru;</div>
    <div class="line"><span class="prefix">e.</span>Pengurus Komisi Pemilihan Umum (KPU), Badan Pengawas Pemilu (Bawaslu) dan Badan Amil Zakat Nasional (BAZNAS);</div>
    <div class="line"><span class="prefix">f.</span>Pengurus Partai Politik.</div>

    <div class="point-intro">
        2. Tidak merangkap jabatan lembaga eksekutif, yudikatif atau legislatif yang meliputi :
    </div>

    <div class="line"><span class="prefix">a.</span>Perangkat Desa/Kelurahan, PNS dengan jabatan Non guru/Pengawas dan TNI/Polri;</div>
    <div class="line"><span class="prefix">b.</span>Anggota Mahkamah Agung, Mahkamah Konstitusi, Komisi Yudisial atau Ombudsman;</div>
    <div class="line"><span class="prefix">c.</span>Anggota Dewan Perwakilan Rakyat atau Dewan Perwakilan Daerah.</div>

    <div class="point-intro">
        3. Apabila dikemudian hari ditemukan ketidaksesuaian dengan regulasi yang berlaku dan dengan Surat pernyataan yang saya buat maka saya siap menerima sanksi dan atau Pengembalian dana yang sudah saya terima ke Kas Negara.
    </div>

    <div class="block">Demikian pernyataan ini kami buat dengan sebenar-benar dan tanpa paksaan dari pihak manapun.</div>

    <table class="ttd-layout">
        <tr>
            <td style="width:54%"></td>
            <td class="ttd-box">
                <div class="ttd-date">{{ $data['kotaTtd'] }}, {{ $data['tanggalSurat'] }}</div>
                <div class="ttd-peran">Yang Membuat Pernyataan,</div>
                <div class="ttd-materai">Materai 10.000</div>
                <div class="ttd-line">....................................................</div>
                <div class="ttd-nama">{{ $data['namaLengkap'] }}</div>
            </td>
        </tr>
    </table>
</div>
@endforeach
</body>
</html>
