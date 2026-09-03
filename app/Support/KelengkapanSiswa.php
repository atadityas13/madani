<?php

namespace App\Support;

use App\Models\OrangTua;
use App\Models\Siswa;

class KelengkapanSiswa
{
    /**
     * @return array{persen: int, wajib_selesai: int, wajib_total: int, wajib_semua_selesai: bool, tab: list<array{id: string, label: string, selesai: bool, wajib: bool, terbuka: bool}>}
     */
    public static function ringkasan(Siswa $siswa): array
    {
        $siswa->loadMissing(['orangTuas', 'periodiks', 'rekamDidik', 'rombels.tahunAjaran', 'prestasis', 'beasiswas', 'dokumens']);

        $tabs = [
            ['id' => 'data-siswa', 'label' => 'Identitas', 'wajib' => true, 'selesai' => self::dataSiswa($siswa)],
            ['id' => 'orang-tua', 'label' => 'Data orang tua', 'wajib' => true, 'selesai' => self::orangTua($siswa)],
            ['id' => 'alamat', 'label' => 'Data alamat', 'wajib' => true, 'selesai' => self::alamat($siswa)],
            ['id' => 'rekam-didik', 'label' => 'Rekam didik', 'wajib' => true, 'selesai' => self::rekamDidik($siswa)],
            ['id' => 'aktivitas', 'label' => 'Riwayat', 'wajib' => false, 'selesai' => self::aktivitas($siswa)],
            ['id' => 'prestasi', 'label' => 'Prestasi', 'wajib' => false, 'selesai' => $siswa->prestasis->isNotEmpty()],
            ['id' => 'beasiswa', 'label' => 'Bantuan', 'wajib' => false, 'selesai' => $siswa->beasiswas->isNotEmpty()],
        ];

        $wajib = array_values(array_filter($tabs, fn (array $tab) => $tab['wajib']));
        $wajibSelesai = count(array_filter($wajib, fn (array $tab) => $tab['selesai']));
        $wajibTotal = count($wajib);
        $wajibSemuaSelesai = $wajibTotal === 0 || $wajibSelesai === $wajibTotal;

        foreach ($tabs as $index => $tab) {
            $tabs[$index]['terbuka'] = self::tabTerbuka($tabs, $tab['id'], $wajibSemuaSelesai);
        }

        return [
            'persen' => $wajibTotal === 0 ? 0 : (int) round(($wajibSelesai / $wajibTotal) * 100),
            'wajib_selesai' => $wajibSelesai,
            'wajib_total' => $wajibTotal,
            'wajib_semua_selesai' => $wajibSemuaSelesai,
            'tab' => $tabs,
        ];
    }

    /**
     * @return array{
     *     persen: int,
     *     wajib_selesai: int,
     *     wajib_total: int,
     *     wajib_semua_selesai: bool,
     *     tab: list<array{id: string, label: string, selesai: bool, wajib: bool, terbuka: bool}>,
     *     tab_terbuka: bool,
     *     tab_selesai: bool,
     *     tab_wajib: bool,
     *     sebelumnya: array{id: string, label: string, selesai: bool, wajib: bool, terbuka: bool}|null,
     *     berikutnya: array{id: string, label: string, selesai: bool, wajib: bool, terbuka: bool}|null,
     *     tab_aman: string
     * }
     */
    public static function navigasi(Siswa $siswa, string $tab = 'data-siswa'): array
    {
        $ringkasan = self::ringkasan($siswa);
        $tabs = $ringkasan['tab'];
        $ids = array_column($tabs, 'id');
        $index = array_search($tab, $ids, true);
        $current = $index === false ? null : $tabs[$index];
        $pertamaBelum = collect($tabs)->first(fn (array $item) => $item['wajib'] && ! $item['selesai']);
        $tabAman = $tab;

        if ($current === null || ! ($current['terbuka'] ?? false)) {
            $tabAman = $pertamaBelum['id'] ?? ($tabs[0]['id'] ?? 'data-siswa');
        }

        return array_merge($ringkasan, [
            'tab_terbuka' => (bool) ($current['terbuka'] ?? false),
            'tab_selesai' => (bool) ($current['selesai'] ?? false),
            'tab_wajib' => (bool) ($current['wajib'] ?? true),
            'sebelumnya' => ($index !== false && $index > 0) ? $tabs[$index - 1] : null,
            'berikutnya' => ($index !== false && $index < count($tabs) - 1) ? $tabs[$index + 1] : null,
            'tab_aman' => $tabAman,
        ]);
    }

    /**
     * Tab yang boleh dibuka siswa: mengikuti urutan kelengkapan wajib.
     */
    public static function tabAman(Siswa $siswa, string $tab): string
    {
        return self::navigasi($siswa, $tab)['tab_aman'];
    }

    /**
     * Tab yang dikenali untuk operator GTK: semua tab bebas dibuka.
     */
    public static function tabDikenal(Siswa $siswa, string $tab): string
    {
        $ids = array_column(self::ringkasan($siswa)['tab'], 'id');

        return in_array($tab, $ids, true) ? $tab : ($ids[0] ?? 'data-siswa');
    }

    /**
     * @param  list<array{id: string, label: string, selesai: bool, wajib: bool}>  $tabs
     */
    private static function tabTerbuka(array $tabs, string $id, bool $wajibSemuaSelesai): bool
    {
        if ($wajibSemuaSelesai) {
            return true;
        }

        $target = collect($tabs)->firstWhere('id', $id);

        if ($target === null || ! ($target['wajib'] ?? false)) {
            return false;
        }

        foreach ($tabs as $tab) {
            if ($tab['id'] === $id) {
                return true;
            }

            if (($tab['wajib'] ?? false) && ! ($tab['selesai'] ?? false)) {
                return false;
            }
        }

        return false;
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
            && self::waliWajib($wali, $ayah, $ibu)
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

    private static function waliWajib(?OrangTua $wali, ?OrangTua $ayah, ?OrangTua $ibu): bool
    {
        $status = $wali?->status;
        $ayahMeninggal = $ayah?->status_hidup === 'meninggal';
        $ibuMeninggal = $ibu?->status_hidup === 'meninggal';

        if ($status === 'Sama dengan ayah kandung') {
            return ! $ayahMeninggal;
        }

        if ($status === 'Sama dengan ibu kandung') {
            return ! $ibuMeninggal;
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
        $rd = $siswa->rekamDidik;

        return filled($rd?->nama_sd)
            && filled($rd?->npsn)
            && filled($rd?->tahun_ajaran_kelulusan)
            && filled($rd?->nip_kepala_sekolah)
            && filled($rd?->nama_kepala_sekolah)
            && filled($rd?->nomor_seri_ijazah)
            && $rd?->tanggal_terbit_ijazah !== null
            && $siswa->dokumenJenis('ijazah_sd') !== null;
    }

    private static function aktivitas(Siswa $siswa): bool
    {
        return $siswa->rombels->contains(fn ($rombel) => $rombel->pivot->status === 'aktif');
    }
}
