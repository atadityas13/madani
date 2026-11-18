<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorJob;
use App\Services\Vendor\VendorJobService;
use App\Support\UkuranHalaman;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorDashboardController extends Controller
{
    public function index(Request $request, VendorJobService $jobs): View
    {
        $user = $request->user();
        abort_unless($user?->adalahVendor(), 403);

        $statistik = $jobs->statistikUntukVendor($user);

        $daftarJob = VendorJob::query()
            ->where('user_id', $user->id)
            ->where('status', VendorJob::STATUS_AKTIF)
            ->withCount('siswas')
            ->orderByDesc('updated_at')
            ->get();

        $jobPertama = $daftarJob->first();

        return view('vendor.dashboard', [
            'statistik' => $statistik,
            'jobs' => UkuranHalaman::paginate($daftarJob, $request, UkuranHalaman::STANDAR, 15),
            'perPage' => UkuranHalaman::label((string) $request->query('per_page', '15'), UkuranHalaman::STANDAR, 15),
            'jobPertama' => $jobPertama,
        ]);
    }
}
