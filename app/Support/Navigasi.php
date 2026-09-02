<?php

namespace App\Support;

class Navigasi
{
    /**
     * Menu MADANI mengikuti pola tampilan EMIS 4.0, isi sesuai kebutuhan madrasah.
     *
     * @return list<array<string, mixed>>
     */
    public static function items(): array
    {
        return [
            [
                'label' => 'Ringkasan',
                'icon' => 'bi-grid',
                'route' => 'dashboard',
            ],
            [
                'label' => 'Kelembagaan',
                'icon' => 'bi-bank',
                'match' => ['kelembagaan.*', 'tahun-ajaran.*'],
                'children' => [
                    ['label' => 'Identitas madrasah', 'route' => 'kelembagaan.identitas'],
                    ['label' => 'Tahun ajaran', 'route' => 'tahun-ajaran.index', 'match' => 'tahun-ajaran.*'],
                ],
            ],
            [
                'label' => 'Siswa',
                'icon' => 'bi-person',
                'match' => ['siswa.*', 'ppdb.*', 'mutasi.*', 'alumni.*'],
                'children' => [
                    ['label' => 'PPDB', 'route' => 'ppdb.index'],
                    ['label' => 'Data siswa', 'route' => 'siswa.index', 'match' => 'siswa.*'],
                    ['label' => 'Mutasi', 'route' => 'mutasi.index'],
                    ['label' => 'Alumni', 'route' => 'alumni.index'],
                ],
            ],
            [
                'label' => 'Guru dan Tendik',
                'icon' => 'bi-people',
                'match' => 'gtk.*',
                'children' => [
                    ['label' => 'Data GTK', 'route' => 'gtk.index', 'match' => 'gtk.*'],
                ],
            ],
            [
                'label' => 'Rombongan Belajar',
                'icon' => 'bi-person-video2',
                'route' => 'rombel.index',
                'match' => 'rombel.*',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function aktif(array $item): bool
    {
        if (! empty($item['match'])) {
            return request()->routeIs($item['match']);
        }

        if (! empty($item['route'])) {
            return request()->routeIs($item['route']);
        }

        foreach ($item['children'] ?? [] as $child) {
            if (self::aktif($child)) {
                return true;
            }
        }

        return false;
    }
}
