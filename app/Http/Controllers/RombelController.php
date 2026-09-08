<?php

namespace App\Http\Controllers;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\Simpatisans\RombelSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class RombelController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Rombel::class);

        $tahunAktif = TahunAjaran::aktif();
        $user = auth()->user();

        $rombels = Rombel::query()
            ->with(['waliKelas', 'tahunAjaran'])
            ->withCount(['siswas as anggota_count' => fn ($query) => $query->where('rombel_siswas.status', 'aktif')])
            ->when($tahunAktif, fn ($query) => $query->where('tahun_ajaran_id', $tahunAktif->id))
            ->when($user?->adalahWali(), fn ($query) => $query->where('gtk_id', $user->gtk_id ?: 0))
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->get();

        return view('rombel.index', compact('rombels', 'tahunAktif'));
    }

    public function syncFromSimpatisans(RombelSyncService $syncService): RedirectResponse
    {
        $this->authorize('create', Rombel::class);

        $tahun = TahunAjaran::aktif();
        if (! $tahun) {
            return redirect()
                ->route('tahun-ajaran.index')
                ->with('error', 'Aktifkan tahun ajaran terlebih dahulu sebelum sinkron rombel.');
        }

        try {
            $result = $syncService->sync($tahun);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('rombel.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('rombel.index')
            ->with('status', sprintf(
                'Sinkron rombel berhasil: %d baru, %d diperbarui (total %d).',
                $result['created'],
                $result['updated'],
                $result['total']
            ));
    }

    public function show(Rombel $rombel): View
    {
        $this->authorize('view', $rombel);
        $rombel->load(['waliKelas', 'tahunAjaran', 'anggotaAktif']);

        $sudahTerisi = Rombel::query()
            ->where('tahun_ajaran_id', $rombel->tahun_ajaran_id)
            ->whereHas('siswas', fn ($query) => $query->where('rombel_siswas.status', 'aktif'))
            ->with(['siswas' => fn ($query) => $query->where('rombel_siswas.status', 'aktif')])
            ->get()
            ->flatMap(fn ($item) => $item->siswas->pluck('id'))
            ->unique()
            ->all();

        $kandidat = Siswa::query()
            ->where('status_keaktifan', '!=', 'nonaktif')
            ->whereNotIn('id', $sudahTerisi)
            ->orderBy('nama')
            ->get();

        return view('rombel.show', compact('rombel', 'kandidat'));
    }

    public function storeAnggota(Request $request, Rombel $rombel): RedirectResponse
    {
        $this->authorize('update', $rombel);
        $data = $request->validate([
            'siswa_ids' => ['required', 'array', 'min:1'],
            'siswa_ids.*' => ['exists:siswas,id'],
        ]);

        DB::transaction(function () use ($data, $rombel) {
            foreach ($data['siswa_ids'] as $siswaId) {
                $siswa = Siswa::query()->findOrFail($siswaId);

                DB::table('rombel_siswas')
                    ->where('siswa_id', $siswa->id)
                    ->where('status', 'aktif')
                    ->whereIn('rombel_id', Rombel::query()->where('tahun_ajaran_id', $rombel->tahun_ajaran_id)->select('id'))
                    ->update(['status' => 'nonaktif']);

                $rombel->siswas()->syncWithoutDetaching([
                    $siswa->id => ['status' => 'aktif'],
                ]);

                if ($siswa->status_keaktifan === 'aktif_tanpa_rombel') {
                    $siswa->update(['status_keaktifan' => 'aktif']);
                }
            }
        });

        return redirect()
            ->route('rombel.show', $rombel)
            ->with('status', 'Siswa ditambahkan ke rombel.');
    }

    public function destroyAnggota(Rombel $rombel, Siswa $siswa): RedirectResponse
    {
        $this->authorize('update', $rombel);
        $rombel->siswas()->updateExistingPivot($siswa->id, ['status' => 'nonaktif']);

        $masihAda = $siswa->rombels()
            ->wherePivot('status', 'aktif')
            ->exists();

        if (! $masihAda && $siswa->status_keaktifan === 'aktif') {
            $siswa->update(['status_keaktifan' => 'aktif_tanpa_rombel']);
        }

        return redirect()
            ->route('rombel.show', $rombel)
            ->with('status', 'Siswa dikeluarkan dari rombel.');
    }
}
