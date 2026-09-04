<?php

namespace App\Support;

use App\Models\User;

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
                'roles' => [Peran::SUPERADMIN, Peran::ADMIN, Peran::WALI_KELAS],
            ],
            [
                'label' => 'Kelembagaan',
                'icon' => 'bi-bank',
                'match' => ['kelembagaan.*', 'tahun-ajaran.*'],
                'roles' => [Peran::SUPERADMIN, Peran::ADMIN],
                'children' => [
                    ['label' => 'Identitas madrasah', 'route' => 'kelembagaan.identitas', 'roles' => [Peran::SUPERADMIN, Peran::ADMIN]],
                    ['label' => 'Tahun ajaran', 'route' => 'tahun-ajaran.index', 'match' => 'tahun-ajaran.*', 'roles' => [Peran::SUPERADMIN, Peran::ADMIN]],
                ],
            ],
            [
                'label' => 'Siswa',
                'icon' => 'bi-person',
                'match' => ['siswa.*', 'ppdb.*', 'mutasi.*', 'alumni.*'],
                'roles' => [Peran::SUPERADMIN, Peran::ADMIN, Peran::WALI_KELAS],
                'children' => [
                    ['label' => 'PPDB', 'route' => 'ppdb.index', 'roles' => [Peran::SUPERADMIN, Peran::ADMIN]],
                    ['label' => 'Data siswa', 'route' => 'siswa.index', 'match' => 'siswa.*', 'roles' => [Peran::SUPERADMIN, Peran::ADMIN, Peran::WALI_KELAS]],
                    ['label' => 'Mutasi', 'route' => 'mutasi.index', 'roles' => [Peran::SUPERADMIN, Peran::ADMIN]],
                    ['label' => 'Alumni', 'route' => 'alumni.index', 'roles' => [Peran::SUPERADMIN, Peran::ADMIN]],
                ],
            ],
            [
                'label' => 'Guru dan Tendik',
                'icon' => 'bi-people',
                'match' => 'gtk.*',
                'roles' => [Peran::SUPERADMIN, Peran::ADMIN],
                'children' => [
                    ['label' => 'Data GTK', 'route' => 'gtk.index', 'match' => 'gtk.*', 'roles' => [Peran::SUPERADMIN, Peran::ADMIN]],
                ],
            ],
            [
                'label' => 'Rombongan Belajar',
                'icon' => 'bi-person-video2',
                'route' => 'rombel.index',
                'match' => 'rombel.*',
                'roles' => [Peran::SUPERADMIN, Peran::ADMIN, Peran::WALI_KELAS],
            ],
            [
                'label' => 'Pengguna',
                'icon' => 'bi-shield-lock',
                'route' => 'pengguna.index',
                'match' => 'pengguna.*',
                'roles' => [Peran::SUPERADMIN],
            ],
            [
                'label' => 'Aplikasi',
                'icon' => 'bi-phone',
                'match' => ['app-updates.*', 'pengumuman.*'],
                'roles' => [Peran::SUPERADMIN, Peran::ADMIN],
                'children' => [
                    [
                        'label' => 'Update Ta\'lim',
                        'route' => 'app-updates.index',
                        'match' => 'app-updates.*',
                        'roles' => [Peran::SUPERADMIN, Peran::ADMIN],
                    ],
                    [
                        'label' => 'Pengumuman',
                        'route' => 'pengumuman.index',
                        'match' => 'pengumuman.*',
                        'roles' => [Peran::SUPERADMIN, Peran::ADMIN],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function untuk(?User $user): array
    {
        return collect(self::items())
            ->map(function (array $item) use ($user) {
                if (! Peran::cocok($user, $item['roles'] ?? array_keys(Peran::labels()))) {
                    return null;
                }

                if (! empty($item['children'])) {
                    $item['children'] = array_values(array_filter(
                        $item['children'],
                        fn (array $child) => Peran::cocok($user, $child['roles'] ?? array_keys(Peran::labels())),
                    ));

                    if ($item['children'] === []) {
                        return null;
                    }
                }

                return $item;
            })
            ->filter()
            ->values()
            ->all();
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
