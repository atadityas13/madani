<?php

namespace App\Support;

class PernyataanIzinSiswa
{
    public const VERSI = 4;

    public static function template(): string
    {
        return 'Saya selaku orang tua/wali menyatakan bahwa anak saya tidak dapat hadir ke madrasah '
            .'pada tanggal {tanggal} karena {jenis}{alasan}, dan saya bertanggungjawab atas kebenaran '
            .'laporan ketidakhadiran ini.';
    }

    public static function teks(?string $tanggalDdMmYyyy = null, ?string $jenis = null, ?string $alasan = null): string
    {
        $tanggal = filled($tanggalDdMmYyyy) ? $tanggalDdMmYyyy : '{tanggal}';
        $jenisText = filled($jenis) ? trim((string) $jenis) : '{jenis}';
        $alasanPart = filled($alasan) ? ' ('.trim((string) $alasan).')' : '';

        return str_replace(
            ['{tanggal}', '{jenis}', '{alasan}'],
            [$tanggal, $jenisText, $alasanPart],
            self::template()
        );
    }

    /**
     * @return array{versi: int, teks: string, template: string}
     */
    public static function payload(): array
    {
        return [
            'versi' => self::VERSI,
            'teks' => self::template(),
            'template' => self::template(),
        ];
    }
}
