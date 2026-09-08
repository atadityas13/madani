<?php

namespace App\Http\Controllers;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\Simpatisans\RombelSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            ->orderByRaw("CASE tingkat WHEN 'VII' THEN 1 WHEN 'VIII' THEN 2 WHEN 'IX' THEN 3 ELSE 9 END")
            ->orderByRaw('CAST(nama AS UNSIGNED)')
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
            ->where('angkatan', $rombel->tingkat)
            ->whereNotIn('id', $sudahTerisi)
            ->orderBy('nama')
            ->get();

        $rombelsTujuan = Rombel::query()
            ->with('waliKelas')
            ->withCount(['siswas as anggota_count' => fn ($query) => $query->where('rombel_siswas.status', 'aktif')])
            ->where('tahun_ajaran_id', $rombel->tahun_ajaran_id)
            ->where('tingkat', $rombel->tingkat)
            ->where('id', '!=', $rombel->id)
            ->orderByRaw("CASE tingkat WHEN 'VII' THEN 1 WHEN 'VIII' THEN 2 WHEN 'IX' THEN 3 ELSE 9 END")
            ->orderByRaw('CAST(nama AS UNSIGNED)')
            ->orderBy('nama')
            ->get();

        return view('rombel.show', compact('rombel', 'kandidat', 'rombelsTujuan'));
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

                if ($siswa->angkatan !== $rombel->tingkat) {
                    throw ValidationException::withMessages([
                        'siswa_ids' => 'Siswa hanya boleh di rombel angkatan '.($rombel->tingkat ?? '—').'.',
                    ]);
                }

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

    public function pindahAnggota(Request $request, Rombel $rombel, Siswa $siswa): RedirectResponse
    {
        $this->authorize('update', $rombel);

        $data = $request->validate([
            'rombel_tujuan_id' => [
                'required',
                'integer',
                Rule::exists('rombels', 'id')
                    ->where('tahun_ajaran_id', $rombel->tahun_ajaran_id)
                    ->where('tingkat', $rombel->tingkat)
                    ->whereNot('id', $rombel->id),
            ],
        ]);

        $tujuan = Rombel::query()->findOrFail($data['rombel_tujuan_id']);

        if ((int) $tujuan->tahun_ajaran_id !== (int) $rombel->tahun_ajaran_id) {
            return redirect()
                ->route('rombel.show', $rombel)
                ->with('error', 'Rombel tujuan harus pada tahun ajaran yang sama.');
        }

        if ($siswa->angkatan !== $tujuan->tingkat) {
            return redirect()
                ->route('rombel.show', $rombel)
                ->with('error', 'Siswa hanya boleh di rombel angkatan '.($siswa->angkatan ?? '—').'.');
        }

        $aktifDiSumber = $rombel->siswas()
            ->wherePivot('status', 'aktif')
            ->where('siswas.id', $siswa->id)
            ->exists();

        if (! $aktifDiSumber) {
            return redirect()
                ->route('rombel.show', $rombel)
                ->with('error', 'Siswa tidak aktif di rombel ini.');
        }

        $this->authorize('update', $tujuan);

        DB::transaction(function () use ($rombel, $tujuan, $siswa) {
            DB::table('rombel_siswas')
                ->where('siswa_id', $siswa->id)
                ->where('status', 'aktif')
                ->whereIn('rombel_id', Rombel::query()->where('tahun_ajaran_id', $rombel->tahun_ajaran_id)->select('id'))
                ->update(['status' => 'nonaktif']);

            $tujuan->siswas()->syncWithoutDetaching([
                $siswa->id => ['status' => 'aktif'],
            ]);

            if ($siswa->status_keaktifan === 'aktif_tanpa_rombel') {
                $siswa->update(['status_keaktifan' => 'aktif']);
            }
        });

        return redirect()
            ->route('rombel.show', $rombel)
            ->with('status', $siswa->nama.' dipindahkan ke rombel '.$tujuan->label().'.');
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

    public function kosongkanAnggota(Rombel $rombel): RedirectResponse
    {
        $this->authorize('update', $rombel);

        DB::transaction(function () use ($rombel) {
            $siswaIds = $rombel->siswas()
                ->wherePivot('status', 'aktif')
                ->pluck('siswas.id');

            DB::table('rombel_siswas')
                ->where('rombel_id', $rombel->id)
                ->where('status', 'aktif')
                ->update(['status' => 'nonaktif']);

            foreach ($siswaIds as $siswaId) {
                $siswa = Siswa::query()->find($siswaId);
                if (! $siswa) {
                    continue;
                }

                $masihAda = $siswa->rombels()
                    ->wherePivot('status', 'aktif')
                    ->exists();

                if (! $masihAda && $siswa->status_keaktifan === 'aktif') {
                    $siswa->update(['status_keaktifan' => 'aktif_tanpa_rombel']);
                }
            }
        });

        return redirect()
            ->route('rombel.show', $rombel)
            ->with('status', 'Semua siswa dikeluarkan dari rombel.');
    }
}
