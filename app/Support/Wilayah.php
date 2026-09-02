<?php

namespace App\Support;

class Wilayah
{
    public static function tree(): array
    {
        return config('wilayah', []);
    }

    public static function options(array $values): array
    {
        if ($values === []) {
            return [];
        }

        return array_combine($values, $values);
    }

    public static function kabupaten(string $provinsi): array
    {
        $items = data_get(self::tree(), $provinsi, []);

        return is_array($items) ? array_keys($items) : [];
    }

    public static function kecamatan(string $provinsi, string $kabupaten): array
    {
        $items = data_get(self::tree(), "{$provinsi}.{$kabupaten}", []);

        return is_array($items) ? array_keys($items) : [];
    }

    public static function desa(string $provinsi, string $kabupaten, string $kecamatan): array
    {
        $items = data_get(self::tree(), "{$provinsi}.{$kabupaten}.{$kecamatan}", []);

        return is_array($items) ? array_keys($items) : [];
    }

    public static function kodePos(string $provinsi, string $kabupaten, string $kecamatan, string $desa): ?string
    {
        $kode = data_get(self::tree(), "{$provinsi}.{$kabupaten}.{$kecamatan}.{$desa}");

        return is_string($kode) && $kode !== '' ? $kode : null;
    }

    public static function formatAlamat(
        ?string $blok,
        ?string $rt,
        ?string $rw,
        ?string $desa,
        ?string $kecamatan,
        ?string $kabupaten,
    ): string {
        $blok = trim((string) $blok);
        $rt = trim((string) $rt);
        $rw = trim((string) $rw);
        $desa = trim((string) $desa);
        $kecamatan = trim((string) $kecamatan);
        $kabupaten = trim((string) $kabupaten);

        if ($blok === '' && $rt === '' && $rw === '' && $desa === '' && $kecamatan === '' && $kabupaten === '') {
            return '';
        }

        return sprintf(
            'Blok %s, RT. %s RW. %s Desa %s Kec. %s Kab. %s',
            $blok,
            $rt,
            $rw,
            $desa,
            $kecamatan,
            $kabupaten,
        );
    }
}
