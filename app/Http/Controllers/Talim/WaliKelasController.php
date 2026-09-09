<?php

namespace App\Http\Controllers\Talim;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WaliKelasDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaliKelasController extends Controller
{
    public function __construct(private WaliKelasDashboardService $dashboard) {}

    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        if (! $this->bolehAkses($user)) {
            return view('talim.wali.akses-ditolak');
        }

        return view('talim.wali.dashboard', $this->dashboard->untuk($request, $user));
    }

    public function siswa(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        if (! $this->bolehAkses($user)) {
            return view('talim.wali.akses-ditolak');
        }

        return view('talim.wali.siswa', $this->dashboard->daftar($request, $user));
    }

    private function bolehAkses(User $user): bool
    {
        return $user->bisaKelola() || $user->adalahWali();
    }
}
