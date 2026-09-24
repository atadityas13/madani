<?php

namespace App\Support;

class PernyataanIzinSiswa
{
    public const VERSI = 1;

    public static function teks(): string
    {
        return 'Saya selaku orang tua/wali menyatakan bahwa laporan izin/sakit ini benar, '
            .'anak saya tidak dapat hadir ke madrasah pada tanggal yang dipilih, '
            .'dan saya bertanggung jawab atas kebenaran pernyataan ini.';
    }

    /**
     * @return array{versi: int, teks: string}
     */
    public static function payload(): array
    {
        return [
            'versi' => self::VERSI,
            'teks' => self::teks(),
        ];
    }
}
