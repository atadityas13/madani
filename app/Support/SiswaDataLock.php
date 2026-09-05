<?php

namespace App\Support;

use App\Models\Siswa;

class SiswaDataLock
{
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
        'foto',
    ];

    public static function aktif(Siswa $siswa): bool
    {
        return $siswa->pernyataan()->exists();
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
