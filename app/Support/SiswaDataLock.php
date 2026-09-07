<?php

namespace App\Support;

use App\Models\PeriodePendataan;
use App\Models\Siswa;

class SiswaDataLock
{
    public const ALASAN_PERNYATAAN = 'pernyataan';

    public const ALASAN_PERIODE = 'periode';

    /** @var list<string> */
    public const BAGIAN_TERKUNCI = [
        'data-siswa',
        'orang-tua',
        'alamat',
        'rekam-didik',
    ];

    /** @var list<string> */
    public const DOKUMEN_TERKUNCI = [
        'kk',
        'akta_lahir',
        'kip',
        'kks',
        'pkh',
        'ijazah_sd',
    ];

    public static function periodeTerbuka(): bool
    {
        $periode = PeriodePendataan::current();

        if ($periode === null) {
            return true;
        }

        return $periode->isCurrentlyOpen();
    }

    public static function pernyataanMengunci(Siswa $siswa): bool
    {
        return $siswa->pernyataan()->exists();
    }

    public static function aktif(Siswa $siswa): bool
    {
        return self::pernyataanMengunci($siswa) || ! self::periodeTerbuka();
    }

    public static function alasan(Siswa $siswa): ?string
    {
        if (self::pernyataanMengunci($siswa)) {
            return self::ALASAN_PERNYATAAN;
        }

        if (! self::periodeTerbuka()) {
            return self::ALASAN_PERIODE;
        }

        return null;
    }

    public static function pesan(Siswa $siswa): string
    {
        return match (self::alasan($siswa)) {
            self::ALASAN_PERNYATAAN => 'Data wajib terkunci setelah pernyataan dikonfirmasi.',
            self::ALASAN_PERIODE => 'Periode pendataan sedang ditutup. Data siswa tidak dapat diubah.',
            default => 'Data siswa terkunci.',
        };
    }

    public static function bolehAksesKartuDanPortofolio(Siswa $siswa): bool
    {
        $lengkap = KelengkapanSiswa::ringkasan($siswa)['wajib_semua_selesai'] ?? false;

        return $lengkap && self::pernyataanMengunci($siswa);
    }

    public static function pesanKartuDanPortofolio(Siswa $siswa): string
    {
        $lengkap = KelengkapanSiswa::ringkasan($siswa)['wajib_semua_selesai'] ?? false;

        if (! $lengkap) {
            return 'Lengkapi semua data wajib terlebih dahulu sebelum membuka kartu atau portofolio.';
        }

        if (! self::pernyataanMengunci($siswa)) {
            return 'Konfirmasi pernyataan terlebih dahulu sebelum membuka kartu atau portofolio.';
        }

        return 'Kartu dan portofolio belum dapat dibuka.';
    }

    public static function bagianTerkunci(string $bagian): bool
    {
        return in_array($bagian, self::BAGIAN_TERKUNCI, true);
    }

    public static function dokumenTerkunci(string $jenis): bool
    {
        return in_array($jenis, self::DOKUMEN_TERKUNCI, true);
    }
}
