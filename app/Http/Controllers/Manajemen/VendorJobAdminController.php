<?php

namespace App\Http\Controllers\Manajemen;

use App\Http\Controllers\Controller;
use App\Models\VendorJob;
use App\Services\Vendor\VendorJobService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VendorJobAdminController extends Controller
{
    public function index(): View
    {
        $this->authorize('create', VendorJob::class);

        $jobs = VendorJob::query()
            ->with(['vendor'])
            ->withCount('siswas')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('manajemen.vendor-jobs.index', compact('jobs'));
    }

    public function create(VendorJobService $jobs): View
    {
        $this->authorize('create', VendorJob::class);

        return view('manajemen.vendor-jobs.create', [
            'job' => new VendorJob(['status' => VendorJob::STATUS_AKTIF]),
            'vendors' => $jobs->daftarVendor(),
            'rombels' => $jobs->rombelAktif(),
            'selectedRombelIds' => old('rombel_ids', []),
        ]);
    }

    public function store(Request $request, VendorJobService $jobs): RedirectResponse
    {
        $this->authorize('create', VendorJob::class);

        $data = $this->validated($request);
        $jobs->buat($request->user(), $data);

        return redirect()
            ->route('manajemen.vendor-jobs.index')
            ->with('status', 'Job vendor dibuat.');
    }

    public function edit(VendorJob $vendorJob, VendorJobService $jobs): View
    {
        $this->authorize('update', $vendorJob);

        $selected = $vendorJob->siswas()
            ->with(['rombels' => fn ($rel) => $rel->wherePivot('status', 'aktif')])
            ->get()
            ->flatMap(fn ($s) => $s->rombels->pluck('id'))
            ->unique()
            ->values()
            ->all();

        return view('manajemen.vendor-jobs.edit', [
            'job' => $vendorJob,
            'vendors' => $jobs->daftarVendor(),
            'rombels' => $jobs->rombelAktif(),
            'selectedRombelIds' => old('rombel_ids', $selected),
        ]);
    }

    public function update(Request $request, VendorJob $vendorJob, VendorJobService $jobs): RedirectResponse
    {
        $this->authorize('update', $vendorJob);

        $data = $this->validated($request);
        $jobs->perbarui($vendorJob, $data);

        return redirect()
            ->route('manajemen.vendor-jobs.index')
            ->with('status', 'Job vendor diperbarui.');
    }

    public function destroy(VendorJob $vendorJob): RedirectResponse
    {
        $this->authorize('delete', $vendorJob);
        $vendorJob->delete();

        return redirect()
            ->route('manajemen.vendor-jobs.index')
            ->with('status', 'Job vendor dihapus.');
    }

    /**
     * @return array{nama: string, user_id: int, status: string, rombel_ids: list<int>}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::in([
                VendorJob::STATUS_DRAFT,
                VendorJob::STATUS_AKTIF,
                VendorJob::STATUS_SELESAI,
            ])],
            'rombel_ids' => ['nullable', 'array'],
            'rombel_ids.*' => ['integer', 'exists:rombels,id'],
        ]);

        $data['rombel_ids'] = array_values(array_map('intval', $data['rombel_ids'] ?? []));

        return $data;
    }
}
