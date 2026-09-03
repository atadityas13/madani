<?php

namespace App\Support;

use App\Models\OrangTua;
use App\Models\Siswa;

class KelengkapanSiswa
{
    /**
     * @return array{persen: int, wajib_selesai: int, wajib_total: int, tab: list<array{id: string, label: string, selesai: bool, wajib: bool}>}
     */
    public static function ringkasan(Siswa $siswa): array
    {
        $siswa->loadMissing(['orangTuas', 'periodiks', 'rekamDidik', 'rombels.tahunAjaran', 'prestasis', 'beasiswas', 'dokumens']);

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
        $siswa->loadMissing('dokumens');

        $hpOk = $siswa->tidak_punya_hp || filled($siswa->no_hp);
        $emailOk = $siswa->tidak_punya_email || filled($siswa->email);
        $kipOk = $periodik?->tidak_punya_kip || filled($periodik?->no_kip);
        $kkDokumen = $siswa->dokumenJenis('kk') !== null;
        $kipDokumen = $periodik?->tidak_punya_kip
            || blank($periodik?->no_kip)
            || $siswa->dokumenJenis('kip') !== null;

        return filled($siswa->nama)
            && filled($siswa->nisn)
            && filled($siswa->nik)
            && filled($siswa->tempat_lahir)
            && $siswa->tanggal_lahir !== null
            && filled($siswa->jenis_kelamin)
            && filled($siswa->agama)
            && filled($siswa->cita_cita)
            && filled($siswa->hobi)
            && $siswa->anak_ke !== null
            && $siswa->jumlah_saudara !== null
            && $hpOk
            && $emailOk
            && filled($periodik?->pembiaya)
            && filled($periodik?->no_kk)
            && filled($periodik?->kepala_keluarga)
            && $kipOk
            && filled($periodik?->kebutuhanKhususLabel())
            && $kkDokumen
            && $kipDokumen;
    }

    private static function orangTua(Siswa $siswa): bool
    {
        $periodik = $siswa->periodikAktif();
        $ayah = $siswa->orangTuas->firstWhere('peran', 'ayah');
        $ibu = $siswa->orangTuas->firstWhere('peran', 'ibu');
        $wali = $siswa->orangTuas->firstWhere('peran', 'wali');

        $kksOk = $periodik?->tidak_punya_kks
            || (filled($periodik?->no_kks) && $siswa->dokumenJenis('kks') !== null);
        $pkhOk = $periodik?->tidak_punya_pkh
            || (filled($periodik?->no_pkh) && $siswa->dokumenJenis('pkh') !== null);

        return self::orangTuaWajib($ayah)
            && self::orangTuaWajib($ibu)
            && self::waliWajib($wali)
            && filled($periodik?->penghasilan_gabungan)
            && $kksOk
            && $pkhOk;
    }

    private static function orangTuaWajib(?OrangTua $ortu): bool
    {
        if (! filled($ortu?->nama) || ! filled($ortu?->status_hidup)) {
            return false;
        }

        if ($ortu->status_hidup !== 'hidup') {
            return true;
        }

        $hpOk = $ortu->tidak_punya_hp || filled($ortu->no_hp);

        return filled($ortu->nik)
            && filled($ortu->tempat_lahir)
            && $ortu->tanggal_lahir !== null
            && filled($ortu->pendidikan)
            && filled($ortu->pekerjaan)
            && filled($ortu->penghasilan)
            && $hpOk;
    }

    private static function waliWajib(?OrangTua $wali): bool
    {
        $status = $wali?->status;

        if ($status === 'Sama dengan ayah kandung' || $status === 'Sama dengan ibu kandung') {
            return true;
        }

        if ($status === 'Lainnya' || $status === 'Isi sendiri') {
            return filled($wali?->hubungan) && self::orangTuaWajib($wali);
        }

        return false;
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
