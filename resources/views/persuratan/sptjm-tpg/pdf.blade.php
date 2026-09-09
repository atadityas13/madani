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
            line-height: 1.25;
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
            margin: 0 0 28px;
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

        /* DomPDF: hanging indent via 2-col table keeps wrap aligned under first word */
        table.poin {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 6px;
        }
        table.poin td {
            vertical-align: top;
            padding: 0;
        }
        table.poin .num {
            width: 18px;
            white-space: nowrap;
        }
        table.poin .body {
            text-align: justify;
        }

        table.sub {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 3px;
        }
        table.sub td {
            vertical-align: top;
            padding: 0;
        }
        table.sub .spacer { width: 18px; }
        table.sub .mark {
            width: 18px;
            white-space: nowrap;
        }
        table.sub .body {
            text-align: justify;
        }

        table.dash {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 2px;
        }
        table.dash td {
            vertical-align: top;
            padding: 0;
        }
        table.dash .spacer { width: 36px; }
        table.dash .mark {
            width: 12px;
            white-space: nowrap;
        }
        table.dash .body {
            text-align: justify;
        }

        .ttd-spacer {
            height: 28px;
        }

        .ttd-layout {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }
        .ttd-layout .pad { width: 70%; }
        .ttd-box {
            width: 30%;
            text-align: left;
        }
        .ttd-date,
        .ttd-peran,
        .ttd-nama {
            margin: 0;
            text-align: left;
            font-weight: normal;
            text-decoration: none;
        }
        .ttd-peran { margin-top: 2px; }
        .ttd-materai {
            height: 110px;
            line-height: 110px;
            text-align: left;
            margin: 0;
        }
        .ttd-nama { margin-top: 2px; }
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

    <table class="poin">
        <tr>
            <td class="num">1.</td>
            <td class="body">Saya guru sertifikasi pada Kantor Kementerian Agama Kabupaten Majalengka dan saya tidak terikat sebagai tenaga tetap selain instansi madrasah. Tenaga tetap dimaksud antara lain sbb :</td>
        </tr>
    </table>

    <table class="sub"><tr><td class="spacer"></td><td class="mark">a.</td><td class="body">Penyuluh Agama;</td></tr></table>
    <table class="sub"><tr><td class="spacer"></td><td class="mark">b.</td><td class="body">Dosen Perguruan Tinggi yang memiliki NIDN atau memiliki NIDN bagi Dokter pendidik klinis penuh waktu atau memiliki NIDK dosen paruh waktu;</td></tr></table>
    <table class="sub"><tr><td class="spacer"></td><td class="mark">c.</td><td class="body">Tenaga Pendamping pada Program Pemerintah</td></tr></table>

    <table class="dash"><tr><td class="spacer"></td><td class="mark">-</td><td class="body">Tenaga Kesejahteraaan Sosial Kecamatan (TKSK)</td></tr></table>
    <table class="dash"><tr><td class="spacer"></td><td class="mark">-</td><td class="body">Program Nasional Pemberdayaan Masyarakat (PNPM)</td></tr></table>
    <table class="dash"><tr><td class="spacer"></td><td class="mark">-</td><td class="body">Pemberdayaan Masyarakat Usaha Tani (PMUT)</td></tr></table>
    <table class="dash"><tr><td class="spacer"></td><td class="mark">-</td><td class="body">Pendamping Korban Tindak Kekerasan dan Pekerja Migran (KTKPM)</td></tr></table>
    <table class="dash"><tr><td class="spacer"></td><td class="mark">-</td><td class="body">Pendamping Keluarga Harapan (PKH)</td></tr></table>
    <table class="dash"><tr><td class="spacer"></td><td class="mark">-</td><td class="body">Tenaga Pendamping Desa</td></tr></table>
    <table class="dash"><tr><td class="spacer"></td><td class="mark">-</td><td class="body">Pemberdayaan Masyarakat Pesisir (PMP)</td></tr></table>

    <table class="sub"><tr><td class="spacer"></td><td class="mark">d.</td><td class="body">Pegawai Pemerintah dengan Perjanjian Kerja (P3K) atau Pegawai Pemerintah Non Pegawai Negeri (PPNPN) bukan Guru;</td></tr></table>
    <table class="sub"><tr><td class="spacer"></td><td class="mark">e.</td><td class="body">Pengurus Komisi Pemilihan Umum (KPU), Badan Pengawas Pemilu (Bawaslu) dan Badan Amil Zakat Nasional (BAZNAS);</td></tr></table>
    <table class="sub"><tr><td class="spacer"></td><td class="mark">f.</td><td class="body">Pengurus Partai Politik.</td></tr></table>

    <table class="poin">
        <tr>
            <td class="num">2.</td>
            <td class="body">Tidak merangkap jabatan lembaga eksekutif, yudikatif atau legislatif yang meliputi :</td>
        </tr>
    </table>

    <table class="sub"><tr><td class="spacer"></td><td class="mark">a.</td><td class="body">Perangkat Desa/Kelurahan, PNS dengan jabatan Non guru/Pengawas dan TNI/Polri;</td></tr></table>
    <table class="sub"><tr><td class="spacer"></td><td class="mark">b.</td><td class="body">Anggota Mahkamah Agung, Mahkamah Konstitusi, Komisi Yudisial atau Ombudsman;</td></tr></table>
    <table class="sub"><tr><td class="spacer"></td><td class="mark">c.</td><td class="body">Anggota Dewan Perwakilan Rakyat atau Dewan Perwakilan Daerah.</td></tr></table>

    <table class="poin">
        <tr>
            <td class="num">3.</td>
            <td class="body">Apabila dikemudian hari ditemukan ketidaksesuaian dengan regulasi yang berlaku dan dengan Surat pernyataan yang saya buat maka saya siap menerima sanksi dan atau Pengembalian dana yang sudah saya terima ke Kas Negara.</td>
        </tr>
    </table>

    <div class="block">Demikian pernyataan ini kami buat dengan sebenar-benar dan tanpa paksaan dari pihak manapun.</div>

    <div class="ttd-spacer"></div>

    <table class="ttd-layout">
        <tr>
            <td class="pad"></td>
            <td class="ttd-box">
                <div class="ttd-date">{{ $data['kotaTtd'] }}, {{ $data['tanggalSurat'] }}</div>
                <div class="ttd-peran">Yang Membuat Pernyataan,</div>
                <div class="ttd-materai">Materai 10.000</div>
                <div class="ttd-nama">{{ $data['namaLengkap'] }}</div>
            </td>
        </tr>
    </table>
</div>
@endforeach
</body>
</html>
