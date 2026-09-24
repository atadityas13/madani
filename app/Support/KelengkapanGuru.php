<?php

namespace App\Support;

use App\Models\Gtk;
use App\Models\User;

class KelengkapanGuru
{
    /**
     * Field yang bisa dilengkapi guru lewat Talim (bukan kepegawaian/kompetensi readonly).
     *
     * @var list<array{id: string, label: string}>
     */
    private const ITEMS = [
        ['id' => 'foto', 'label' => 'Foto profil'],
        ['id' => 'jenis_kelamin', 'label' => 'Jenis kelamin'],
        ['id' => 'tempat_lahir', 'label' => 'Tempat lahir'],
        ['id' => 'tanggal_lahir', 'label' => 'Tanggal lahir'],
        ['id' => 'agama', 'label' => 'Agama'],
        ['id' => 'nomor_hp', 'label' => 'Nomor HP'],
        ['id' => 'email', 'label' => 'Email'],
        ['id' => 'alamat', 'label' => 'Alamat'],
    ];

    /**
     * @return array{
     *     persen: int,
     *     selesai: int,
     *     total: int,
     *     semua_selesai: bool,
     *     items: list<array{id: string, label: string, selesai: bool}>
     * }
     */
    public static function ringkasan(User $user): array
    {
        $user->loadMissing('gtk');
        $gtk = $user->gtk;

        $items = [];
        foreach (self::ITEMS as $def) {
            $items[] = [
                'id' => $def['id'],
                'label' => $def['label'],
                'selesai' => self::fieldSelesai($def['id'], $user, $gtk),
            ];
        }

        $total = count($items);
        $selesai = count(array_filter($items, fn (array $item) => $item['selesai']));
        $semuaSelesai = $total > 0 && $selesai === $total;

        return [
            'persen' => $total === 0 ? 0 : (int) round(($selesai / $total) * 100),
            'selesai' => $selesai,
            'total' => $total,
            'semua_selesai' => $semuaSelesai,
            'items' => $items,
        ];
    }

    private static function fieldSelesai(string $id, User $user, ?Gtk $gtk): bool
    {
        return match ($id) {
            'foto' => filled($user->foto) || filled($gtk?->foto_url),
            'jenis_kelamin' => filled($gtk?->jenis_kelamin),
            'tempat_lahir' => filled($gtk?->tempat_lahir),
            'tanggal_lahir' => $gtk?->tanggal_lahir !== null,
            'agama' => filled($gtk?->agama),
            'nomor_hp' => filled($gtk?->nomor_hp),
            'email' => filled($gtk?->email),
            'alamat' => filled($gtk?->alamat),
            default => false,
        };
    }
}
