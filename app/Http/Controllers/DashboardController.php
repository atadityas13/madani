<?php

namespace App\Http\Controllers;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $tahunAktif = TahunAjaran::aktif();
        $siswaQuery = Siswa::query();
        $rombelQuery = Rombel::query()
            ->when($tahunAktif, fn ($query) => $query->where('tahun_ajaran_id', $tahunAktif->id));

        if ($user?->adalahWali()) {
            $ids = $user->rombelIdsAktif();
            $rombelQuery->where('gtk_id', $user->gtk_id ?: 0);

            if ($ids === []) {
                $siswaQuery->whereRaw('0 = 1');
            } else {
                $siswaQuery->whereHas('rombels', fn ($query) => $query
                    ->whereIn('rombels.id', $ids)
                    ->where('rombel_siswas.status', 'aktif'));
            }
        }

        return view('dashboard', [
            'tahunAktif' => $tahunAktif,
            'jumlahSiswa' => (clone $siswaQuery)->count(),
            'siswaAktif' => (clone $siswaQuery)->where('status_keaktifan', 'aktif')->count(),
            'tanpaRombel' => $user?->adalahWali()
                ? 0
                : Siswa::query()->where('status_keaktifan', 'aktif_tanpa_rombel')->count(),
            'jumlahRombel' => $rombelQuery->count(),
        ]);
    }
}
