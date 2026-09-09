<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class JurnalRankingService
{
    /**
     * @return Collection<int, array{rank: int, nama: string, nip: string|null, jumlah: int}>
     */
    public function terbanyak(int $limit = 10): Collection
    {
        $limit = max(1, min($limit, 50));

        return User::query()
            ->with('gtk:id,nama,gelar_depan,gelar_belakang,nip')
            ->withCount('jurnalPembelajarans as jumlah_jurnal')
            ->whereHas('jurnalPembelajarans')
            ->orderByDesc('jumlah_jurnal')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->values()
            ->map(fn (User $user, int $index) => [
                'rank' => $index + 1,
                'nama' => $user->gtk?->nama_lengkap ?: $user->name,
                'nip' => $user->gtk?->nip ?: $user->username,
                'jumlah' => (int) $user->jumlah_jurnal,
            ]);
    }
}
