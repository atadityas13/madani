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
        return view('dashboard', [
            'tahunAktif' => TahunAjaran::aktif(),
            'jumlahSiswa' => Siswa::query()->count(),
            'siswaAktif' => Siswa::query()->where('status_keaktifan', 'aktif')->count(),
            'tanpaRombel' => Siswa::query()->where('status_keaktifan', 'aktif_tanpa_rombel')->count(),
            'jumlahRombel' => Rombel::query()->count(),
        ]);
    }
}
