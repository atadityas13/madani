<?php

namespace App\Support;

use App\Models\Siswa;

class KelengkapanSiswa
{
    /**
     * @return array{persen: int, wajib_selesai: int, wajib_total: int, tab: list<array{id: string, label: string, selesai: bool, wajib: bool}>}
     */
    public static function ringkasan(Siswa $siswa): array
    {
        $siswa->loadMissing(['orangTuas', 'periodiks', 'rekamDidik', 'rombels.tahunAjaran', 'prestasis', 'beasiswas']);

        $tabs = [
            ['id' => 'data-siswa', 'label' => 'Data siswa', 'wajib' => true, 'selesai' => self::dataSiswa($siswa)],
            ['id' => 'orang-tua', 'label' => 'Data orang tua', 'wajib' => true, 'selesai' => self::orangTua($siswa)],
            ['id' => 'alamat', 'label' => 'Data alamat', 'wajib' => true, 'selesai' => self::alamat($siswa)],
            ['id' => 'rekam-didik', 'label' => 'Rekam didik', 'wajib' => true, 'selesai' => self::rekamDidik($siswa)],
            ['id' => 'aktivitas', 'label' => 'Riwayat akademik', 'wajib' => true, 'selesai' => self::aktivitas($siswa)],
            ['id' => 'prestasi', 'label' => 'Prestasi', 'wajib' => false, 'selesai' => $siswa->prestasis->isNotEmpty()],
            ['id' => 'beasiswa', 'label' => 'Bantuan pendidikan', 'wajib' => false, 'selesai' => $siswa->beasiswas->isNotEmpty()],
        ];

        $wajib = array_values(array_filter($tabs, fn (array $tab) => $tab['wajib']));
        $wajibSelesai = count(array_filter($wajib, fn (array $tab) => $tab['selesai']));
        $wajibTotal = count($wajib);

        return [
            'persen' => $wajibTotal === 0 ? 0 : (int) round(($wajibSelesai / $wajibTotal) * 100),
            'wajib_selesai' => $wajibSelesai,
            'wajib_total' => $wajibTotal,
            'tab' => $tabs,
        ];
    }

    private static function dataSiswa(Siswa $siswa): bool
    {
        $periodik = $siswa->periodikAktif();

        return filled($siswa->nama)
            && filled($siswa->nisn)
            && filled($siswa->nik)
            && filled($siswa->tempat_lahir)
            && $siswa->tanggal_lahir !== null
            && filled($siswa->jenis_kelamin)
            && filled($siswa->agama)
            && filled($siswa->cita_cita)
            && $siswa->anak_ke !== null
            && $siswa->jumlah_saudara !== null
            && filled($periodik?->pembiaya);
    }

    private static function orangTua(Siswa $siswa): bool
    {
        $ayah = $siswa->orangTuas->firstWhere('peran', 'ayah');
        $ibu = $siswa->orangTuas->firstWhere('peran', 'ibu');

        return filled($ayah?->nama) && filled($ibu?->nama);
    }

    private static function alamat(Siswa $siswa): bool
    {
        $periodik = $siswa->periodikAktif();

        return filled($periodik?->tempat_tinggal) && filled($periodik?->desa);
    }

    private static function rekamDidik(Siswa $siswa): bool
    {
        return filled($siswa->rekamDidik?->nama_sd);
    }

    private static function aktivitas(Siswa $siswa): bool
    {
        return $siswa->rombels->contains(fn ($rombel) => $rombel->pivot->status === 'aktif');
    }
}
