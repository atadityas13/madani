<?php

namespace App\Http\Controllers;

use App\Models\Gtk;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RombelController extends Controller
{
    public function index(): View
    {
        $tahunAktif = TahunAjaran::aktif();

        $rombels = Rombel::query()
            ->with(['waliKelas', 'tahunAjaran'])
            ->withCount(['siswas as anggota_count' => fn ($query) => $query->where('rombel_siswas.status', 'aktif')])
            ->when($tahunAktif, fn ($query) => $query->where('tahun_ajaran_id', $tahunAktif->id))
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->get();

        return view('rombel.index', compact('rombels', 'tahunAktif'));
    }

    public function create(): View|RedirectResponse
    {
        if (! TahunAjaran::aktif()) {
            return redirect()
                ->route('tahun-ajaran.index')
                ->with('status', 'Aktifkan tahun ajaran terlebih dahulu sebelum membuat rombel.');
        }

        return view('rombel.form', [
            'rombel' => new Rombel([
                'jenis_rombel' => 'Reguler',
                'waktu_mengajar' => 'Pagi',
                'kurikulum' => 'Kurikulum Merdeka',
            ]),
            'gtks' => Gtk::query()->where('status', 'aktif')->orderBy('nama')->get(),
            'tahunAktif' => TahunAjaran::aktif(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tahun = TahunAjaran::aktif();

        if (! $tahun) {
            return redirect()->route('tahun-ajaran.index')->with('status', 'Belum ada tahun ajaran aktif.');
        }

        $rombel = Rombel::query()->create([
            ...$this->validated($request, $tahun->id),
            'tahun_ajaran_id' => $tahun->id,
        ]);

        return redirect()
            ->route('rombel.show', $rombel)
            ->with('status', 'Rombel ditambahkan. Lanjutkan dengan menambahkan siswa.');
    }

    public function show(Rombel $rombel): View
    {
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

    public function edit(Rombel $rombel): View
    {
        return view('rombel.form', [
            'rombel' => $rombel,
            'gtks' => Gtk::query()->where('status', 'aktif')->orderBy('nama')->get(),
            'tahunAktif' => $rombel->tahunAjaran,
        ]);
    }

    public function update(Request $request, Rombel $rombel): RedirectResponse
    {
        $rombel->update($this->validated($request, $rombel->tahun_ajaran_id, $rombel));

        return redirect()
            ->route('rombel.show', $rombel)
            ->with('status', 'Rombel diperbarui.');
    }

    public function destroy(Rombel $rombel): RedirectResponse
    {
        $rombel->delete();

        return redirect()->route('rombel.index')->with('status', 'Rombel dihapus.');
    }

    public function storeAnggota(Request $request, Rombel $rombel): RedirectResponse
    {
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

    private function validated(Request $request, int $tahunAjaranId, ?Rombel $rombel = null): array
    {
        return $request->validate([
            'tingkat' => ['required', 'integer', Rule::in([7, 8, 9])],
            'nama' => [
                'required',
                'string',
                'max:30',
                Rule::unique('rombels', 'nama')
                    ->where('tahun_ajaran_id', $tahunAjaranId)
                    ->where('tingkat', $request->integer('tingkat'))
                    ->ignore($rombel?->id),
            ],
            'gtk_id' => ['nullable', 'exists:gtks,id'],
            'ruangan' => ['nullable', 'string', 'max:50'],
            'jenis_rombel' => ['nullable', 'string', Rule::in(array_keys(config('emis.jenis_rombel')))],
            'waktu_mengajar' => ['nullable', 'string', Rule::in(array_keys(config('emis.waktu_mengajar')))],
            'kurikulum' => ['nullable', 'string', Rule::in(array_keys(config('emis.kurikulum')))],
            'program' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
