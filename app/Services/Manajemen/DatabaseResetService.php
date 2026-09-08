<?php

namespace App\Services\Manajemen;

use App\Models\AppMaintenance;
use App\Models\AppMenu;
use App\Models\AppUpdate;
use App\Models\Beasiswa;
use App\Models\DeviceToken;
use App\Models\Dokumen;
use App\Models\Gtk;
use App\Models\JurnalPembelajaran;
use App\Models\Madrasah;
use App\Models\Notifikasi;
use App\Models\NotifikasiRead;
use App\Models\NotifMedia;
use App\Models\OrangTua;
use App\Models\PengajuanPerubahanSiswa;
use App\Models\PeriodePendataan;
use App\Models\Prestasi;
use App\Models\RekamDidik;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\SiswaPeriodik;
use App\Models\SiswaPernyataan;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Peran;
use Database\Seeders\AppMenuSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Laravel\Sanctum\PersonalAccessToken;

class DatabaseResetService
{
    /**
     * @return list<string>
     */
    public static function modulDiizinkan(): array
    {
        return [
            'siswa',
            'gtk',
            'rombel',
            'tahun-ajaran',
            'periode-pendataan',
            'jurnal',
            'notifikasi',
            'identitas',
            'app-settings',
        ];
    }

    /**
     * @return array{label: string, dihapus: int, pesan: string}
     */
    public function kosongkan(string $modul): array
    {
        if (! in_array($modul, self::modulDiizinkan(), true)) {
            throw new InvalidArgumentException("Modul tidak dikenal: {$modul}");
        }

        return match ($modul) {
            'siswa' => $this->kosongkanSiswa(),
            'gtk' => $this->kosongkanGtk(),
            'rombel' => $this->kosongkanRombel(),
            'tahun-ajaran' => $this->kosongkanTahunAjaran(),
            'periode-pendataan' => $this->kosongkanPeriodePendataan(),
            'jurnal' => $this->kosongkanJurnal(),
            'notifikasi' => $this->kosongkanNotifikasi(),
            'identitas' => $this->kosongkanIdentitas(),
            'app-settings' => $this->kosongkanAppSettings(),
        };
    }

    /**
     * @return list<array{id: string, label: string, ringkasan: string, excel: bool, confirm: string}>
     */
    public function kartu(): array
    {
        return [
            [
                'id' => 'siswa',
                'label' => 'Data siswa',
                'ringkasan' => number_format(Siswa::withTrashed()->count()).' siswa (termasuk soft-delete)',
                'excel' => true,
                'confirm' => 'Kosongkan seluruh data siswa termasuk akun login Ta\'lim, biodata, dokumen, dan file terkait? Tindakan ini tidak bisa dibatalkan.',
            ],
            [
                'id' => 'gtk',
                'label' => 'GTK',
                'ringkasan' => number_format(Gtk::query()->count()).' GTK',
                'excel' => true,
                'confirm' => 'Kosongkan seluruh data GTK dan akun Ta\'lim/wali yang terikat GTK (Super Admin tidak dihapus)? Tindakan ini tidak bisa dibatalkan.',
            ],
            [
                'id' => 'rombel',
                'label' => 'Rombel & anggota',
                'ringkasan' => number_format(Rombel::query()->count()).' rombel',
                'excel' => true,
                'confirm' => 'Hapus seluruh rombel dan keanggotaan siswa? Data siswa tidak ikut dihapus. Tindakan ini tidak bisa dibatalkan.',
            ],
            [
                'id' => 'tahun-ajaran',
                'label' => 'Tahun ajaran',
                'ringkasan' => number_format(TahunAjaran::query()->count()).' tahun ajaran',
                'excel' => true,
                'confirm' => 'Hapus seluruh tahun ajaran beserta semua rombel, anggota, dan data periodik siswa per tahun ajaran? Identitas siswa tetap ada. Tindakan ini tidak bisa dibatalkan.',
            ],
            [
                'id' => 'periode-pendataan',
                'label' => 'Periode pendataan',
                'ringkasan' => number_format(PeriodePendataan::query()->count()).' periode',
                'excel' => false,
                'confirm' => 'Hapus seluruh periode pendataan? Tindakan ini tidak bisa dibatalkan.',
            ],
            [
                'id' => 'jurnal',
                'label' => 'Jurnal pembelajaran',
                'ringkasan' => number_format(JurnalPembelajaran::query()->count()).' jurnal',
                'excel' => true,
                'confirm' => 'Hapus seluruh jurnal pembelajaran? Tindakan ini tidak bisa dibatalkan.',
            ],
            [
                'id' => 'notifikasi',
                'label' => 'Notifikasi & media',
                'ringkasan' => number_format(Notifikasi::query()->count()).' notifikasi · '.number_format(NotifMedia::query()->count()).' media',
                'excel' => false,
                'confirm' => 'Hapus seluruh notifikasi, riwayat baca, media, dan device token? Tindakan ini tidak bisa dibatalkan.',
            ],
            [
                'id' => 'identitas',
                'label' => 'Identitas madrasah',
                'ringkasan' => filled(Madrasah::query()->value('nama'))
                    ? (string) Madrasah::query()->value('nama')
                    : 'Belum diisi',
                'excel' => false,
                'confirm' => 'Reset identitas madrasah ke kosong (logo ikut dihapus)? Tindakan ini tidak bisa dibatalkan.',
            ],
            [
                'id' => 'app-settings',
                'label' => 'Menu / app settings',
                'ringkasan' => number_format(AppMenu::query()->count()).' menu · '
                    .number_format(AppUpdate::query()->count()).' update · '
                    .number_format(AppMaintenance::query()->count()).' maintenance',
                'excel' => false,
                'confirm' => 'Hapus menu Ta\'lim, update, dan maintenance lalu seed ulang menu bawaan? Tindakan ini tidak bisa dibatalkan.',
            ],
        ];
    }

    /**
     * @return array{label: string, dihapus: int, pesan: string}
     */
    private function kosongkanSiswa(): array
    {
        $paths = $this->kumpulkanPathSiswa();
        $jumlah = 0;

        DB::transaction(function () use (&$jumlah) {
            $siswaIds = Siswa::withTrashed()->pluck('id')->all();

            if ($siswaIds !== []) {
                PersonalAccessToken::query()
                    ->where('tokenable_type', Siswa::class)
                    ->whereIn('tokenable_id', $siswaIds)
                    ->delete();
            }

            DB::table('rombel_siswas')->delete();
            PengajuanPerubahanSiswa::query()->delete();
            SiswaPernyataan::query()->delete();
            Prestasi::query()->delete();
            Beasiswa::query()->delete();
            Dokumen::query()->delete();
            RekamDidik::query()->delete();
            OrangTua::query()->delete();
            SiswaPeriodik::query()->delete();

            $jumlah = Siswa::withTrashed()->count();
            Siswa::withTrashed()->forceDelete();
        });

        $this->hapusPathR2($paths);

        return [
            'label' => 'Data siswa',
            'dihapus' => $jumlah,
            'pesan' => "Berhasil mengosongkan {$jumlah} data siswa beserta akun Ta'lim dan relasinya.",
        ];
    }

    /**
     * @return array{label: string, dihapus: int, pesan: string}
     */
    private function kosongkanGtk(): array
    {
        $jumlah = 0;

        DB::transaction(function () use (&$jumlah) {
            Rombel::query()->update([
                'gtk_id' => null,
                'wali_kelas_id' => null,
            ]);

            $gtkIds = Gtk::query()->pluck('id')->all();
            $jumlah = count($gtkIds);

            if ($gtkIds !== []) {
                User::query()
                    ->whereIn('gtk_id', $gtkIds)
                    ->get()
                    ->each(function (User $user) {
                        if ($user->hasRole(Peran::SUPERADMIN)) {
                            $user->forceFill(['gtk_id' => null])->save();

                            return;
                        }

                        $user->tokens()->delete();
                        $user->syncRoles([]);
                        $user->delete();
                    });
            }

            Gtk::query()->delete();
        });

        return [
            'label' => 'GTK',
            'dihapus' => $jumlah,
            'pesan' => "Berhasil mengosongkan {$jumlah} data GTK (akun Super Admin dipertahankan).",
        ];
    }

    /**
     * @return array{label: string, dihapus: int, pesan: string}
     */
    private function kosongkanRombel(): array
    {
        $jumlah = 0;

        DB::transaction(function () use (&$jumlah) {
            DB::table('rombel_siswas')->delete();
            $jumlah = Rombel::query()->count();
            Rombel::query()->delete();
        });

        return [
            'label' => 'Rombel & anggota',
            'dihapus' => $jumlah,
            'pesan' => "Berhasil menghapus {$jumlah} rombel beserta keanggotaannya.",
        ];
    }

    /**
     * @return array{label: string, dihapus: int, pesan: string}
     */
    private function kosongkanTahunAjaran(): array
    {
        $jumlah = 0;

        DB::transaction(function () use (&$jumlah) {
            DB::table('rombel_siswas')->delete();
            Rombel::query()->delete();
            $jumlah = TahunAjaran::query()->count();
            TahunAjaran::query()->delete();
        });

        return [
            'label' => 'Tahun ajaran',
            'dihapus' => $jumlah,
            'pesan' => "Berhasil menghapus {$jumlah} tahun ajaran beserta rombel terkait.",
        ];
    }

    /**
     * @return array{label: string, dihapus: int, pesan: string}
     */
    private function kosongkanPeriodePendataan(): array
    {
        $jumlah = PeriodePendataan::query()->count();
        PeriodePendataan::query()->delete();

        return [
            'label' => 'Periode pendataan',
            'dihapus' => $jumlah,
            'pesan' => "Berhasil menghapus {$jumlah} periode pendataan.",
        ];
    }

    /**
     * @return array{label: string, dihapus: int, pesan: string}
     */
    private function kosongkanJurnal(): array
    {
        $jumlah = JurnalPembelajaran::query()->count();
        JurnalPembelajaran::query()->delete();

        return [
            'label' => 'Jurnal pembelajaran',
            'dihapus' => $jumlah,
            'pesan' => "Berhasil menghapus {$jumlah} jurnal pembelajaran.",
        ];
    }

    /**
     * @return array{label: string, dihapus: int, pesan: string}
     */
    private function kosongkanNotifikasi(): array
    {
        $paths = NotifMedia::query()->pluck('path')->filter()->values()->all();
        $gambar = Notifikasi::query()->pluck('gambar_url')->filter()->values()->all();
        $audio = Notifikasi::query()->pluck('audio_url')->filter()->values()->all();

        $jumlah = 0;

        DB::transaction(function () use (&$jumlah) {
            $jumlahNotif = Notifikasi::query()->count();
            $jumlahMedia = NotifMedia::query()->count();
            $jumlahToken = DeviceToken::query()->count();

            NotifikasiRead::query()->delete();
            Notifikasi::query()->delete();
            NotifMedia::query()->delete();
            DeviceToken::query()->delete();

            $jumlah = $jumlahNotif + $jumlahMedia + $jumlahToken;
        });

        $this->hapusPathR2(array_merge($paths, $this->pathLokalDariUrl($gambar), $this->pathLokalDariUrl($audio)));

        return [
            'label' => 'Notifikasi & media',
            'dihapus' => $jumlah,
            'pesan' => "Berhasil mengosongkan notifikasi, media, dan device token ({$jumlah} baris).",
        ];
    }

    /**
     * @return array{label: string, dihapus: int, pesan: string}
     */
    private function kosongkanIdentitas(): array
    {
        $madrasah = Madrasah::query()->first();
        $logo = $madrasah?->logo_path;

        if ($madrasah) {
            $madrasah->forceFill([
                'nama' => '',
                'npsn' => null,
                'nsm' => null,
                'jenjang' => null,
                'status' => null,
                'akreditasi' => null,
                'alamat' => null,
                'desa' => null,
                'kecamatan' => null,
                'kota' => null,
                'provinsi' => null,
                'kode_pos' => null,
                'telepon' => null,
                'email' => null,
                'website' => null,
                'logo_path' => null,
            ])->save();
        }

        if (filled($logo)) {
            $this->hapusPathR2([(string) $logo]);
        }

        return [
            'label' => 'Identitas madrasah',
            'dihapus' => $madrasah ? 1 : 0,
            'pesan' => 'Identitas madrasah berhasil direset.',
        ];
    }

    /**
     * @return array{label: string, dihapus: int, pesan: string}
     */
    private function kosongkanAppSettings(): array
    {
        $paths = AppMenu::query()->pluck('icon_path')->filter()->values()->all();
        $jumlah = 0;

        DB::transaction(function () use (&$jumlah) {
            $jumlah = AppMenu::query()->count()
                + AppUpdate::query()->count()
                + AppMaintenance::query()->count();

            AppMenu::query()->delete();
            AppUpdate::query()->delete();
            AppMaintenance::query()->delete();
        });

        $this->hapusPathR2($paths);
        (new AppMenuSeeder)->run();

        return [
            'label' => 'Menu / app settings',
            'dihapus' => $jumlah,
            'pesan' => "Pengaturan app dikosongkan ({$jumlah} baris) dan menu bawaan di-seed ulang.",
        ];
    }

    /**
     * @return list<string>
     */
    private function kumpulkanPathSiswa(): array
    {
        $paths = [];

        foreach (Siswa::withTrashed()->pluck('foto') as $foto) {
            if (filled($foto)) {
                $paths[] = (string) $foto;
            }
        }

        foreach (Dokumen::query()->pluck('path') as $path) {
            if (filled($path)) {
                $paths[] = (string) $path;
            }
        }

        foreach (Prestasi::query()->pluck('sertifikat_path') as $path) {
            if (filled($path)) {
                $paths[] = (string) $path;
            }
        }

        foreach (Beasiswa::query()->pluck('bukti_path') as $path) {
            if (filled($path)) {
                $paths[] = (string) $path;
            }
        }

        foreach (SiswaPernyataan::query()->get(['ttd_siswa_path', 'ttd_wali_path']) as $item) {
            if (filled($item->ttd_siswa_path)) {
                $paths[] = (string) $item->ttd_siswa_path;
            }
            if (filled($item->ttd_wali_path)) {
                $paths[] = (string) $item->ttd_wali_path;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param  list<string>  $urls
     * @return list<string>
     */
    private function pathLokalDariUrl(array $urls): array
    {
        $paths = [];

        foreach ($urls as $url) {
            if (! is_string($url) || $url === '') {
                continue;
            }

            if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
                $paths[] = $url;
            }
        }

        return $paths;
    }

    /**
     * @param  list<string>  $paths
     */
    private function hapusPathR2(array $paths): void
    {
        $bersih = array_values(array_unique(array_filter($paths)));

        if ($bersih === []) {
            return;
        }

        try {
            Storage::disk('r2')->delete($bersih);
        } catch (\Throwable) {
            // Best-effort: gagal hapus file tidak membatalkan wipe DB.
        }
    }
}
