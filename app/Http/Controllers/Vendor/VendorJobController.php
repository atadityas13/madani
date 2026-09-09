<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\VendorJob;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorJobController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', VendorJob::class);

        $user = $request->user();
        $foto = (string) $request->query('foto', '');

        $jobs = VendorJob::query()
            ->when($user->adalahVendor(), fn ($q) => $q->where('user_id', $user->id))
            ->withCount([
                'siswas',
                'siswas as sudah_foto_count' => fn ($q) => $q->whereNotNull('foto')->where('foto', '!=', ''),
            ])
            ->when($foto === 'sudah', fn ($q) => $q->having('sudah_foto_count', '>', 0))
            ->when($foto === 'belum', function ($q) {
                $q->havingRaw('siswas_count > sudah_foto_count');
            })
            ->orderByDesc('updated_at')
            ->get();

        return view('vendor.jobs.index', compact('jobs', 'foto'));
    }

    public function show(Request $request, VendorJob $vendorJob): View
    {
        $this->authorize('view', $vendorJob);

        $q = trim((string) $request->query('q', ''));
        $foto = (string) $request->query('foto', 'semua');
        $rombelId = $request->query('rombel_id');

        $siswas = $vendorJob->siswas()
            ->with(['rombels' => fn ($rel) => $rel->wherePivot('status', 'aktif')])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%");
                });
            })
            ->when($foto === 'sudah', fn ($query) => $query->whereNotNull('foto')->where('foto', '!=', ''))
            ->when($foto === 'belum', fn ($query) => $query->where(fn ($inner) => $inner->whereNull('foto')->orWhere('foto', '')))
            ->when(filled($rombelId), function ($query) use ($rombelId) {
                $query->whereHas('rombels', fn ($r) => $r
                    ->where('rombels.id', $rombelId)
                    ->where('rombel_siswas.status', 'aktif'));
            })
            ->orderBy('nama')
            ->get();

        $total = $vendorJob->siswas()->count();
        $sudahFoto = $vendorJob->siswas()->whereNotNull('foto')->where('foto', '!=', '')->count();
        $progress = $total > 0 ? (int) round(($sudahFoto / $total) * 100) : 0;

        $rombels = $vendorJob->siswas()
            ->with(['rombels' => fn ($rel) => $rel->wherePivot('status', 'aktif')])
            ->get()
            ->flatMap(fn (Siswa $s) => $s->rombels)
            ->unique('id')
            ->sortBy('nama')
            ->values();

        return view('vendor.jobs.show', [
            'job' => $vendorJob,
            'siswas' => $siswas,
            'q' => $q,
            'foto' => $foto,
            'rombelId' => $rombelId,
            'rombels' => $rombels,
            'total' => $total,
            'sudahFoto' => $sudahFoto,
            'progress' => $progress,
        ]);
    }
}
