<?php

namespace App\Http\Controllers\Talim;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WaliKelasDashboardService;
use App\Support\Peran;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaliKelasController extends Controller
{
    public function __construct(private WaliKelasDashboardService $dashboard) {}

    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        // Wali ditentukan dari penugasan rombel (gtk_id), bukan hanya peran Spatie.
        if (! $this->bolehAkses($user)) {
            return view('talim.wali.akses-ditolak');
        }

        return view('talim.wali.dashboard', $this->dashboard->untuk($user));
    }

    private function bolehAkses(User $user): bool
    {
        if ($user->bisaKelola() || $user->hasRole(Peran::WALI_KELAS)) {
            return true;
        }

        return $this->dashboard->rombelWali($user) !== null;
    }
}
