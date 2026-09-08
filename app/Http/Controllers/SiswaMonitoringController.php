<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\User;
use App\Services\SiswaMonitoringService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiswaMonitoringController extends Controller
{
    public function __construct(private SiswaMonitoringService $monitoring) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Siswa::class);

        /** @var User $user */
        $user = $request->user();

        return view('siswa.monitoring', $this->monitoring->halaman($request, $user));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Siswa::class);

        /** @var User $user */
        $user = $request->user();

        return $this->monitoring->ekspor($request, $user);
    }
}
