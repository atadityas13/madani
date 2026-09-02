<?php

namespace App\Http\Controllers;

use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TahunAjaranController extends Controller
{
    public function index(): View
    {
        return view('tahun-ajaran.index', [
            'tahunAjarans' => TahunAjaran::query()->orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function create(): View
    {
        return view('tahun-ajaran.form', [
            'tahunAjaran' => new TahunAjaran([
                'semester' => 'ganjil',
                'tanggal_mulai' => now()->startOfYear()->month(7)->day(13),
                'tanggal_selesai' => now()->addYear()->month(6)->day(12),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tahun = TahunAjaran::query()->create($this->validated($request));

        if ($tahun->is_aktif) {
            $this->jadikanAktif($tahun);
        }

        return redirect()
            ->route('tahun-ajaran.index')
            ->with('status', 'Tahun ajaran disimpan.');
    }

    public function edit(TahunAjaran $tahunAjaran): View
    {
        return view('tahun-ajaran.form', compact('tahunAjaran'));
    }

    public function update(Request $request, TahunAjaran $tahunAjaran): RedirectResponse
    {
        $tahunAjaran->update($this->validated($request, $tahunAjaran));

        if ($tahunAjaran->is_aktif) {
            $this->jadikanAktif($tahunAjaran);
        }

        return redirect()
            ->route('tahun-ajaran.index')
            ->with('status', 'Tahun ajaran diperbarui.');
    }

    public function aktifkan(TahunAjaran $tahunAjaran): RedirectResponse
    {
        $this->jadikanAktif($tahunAjaran);

        return redirect()
            ->route('tahun-ajaran.index')
            ->with('status', $tahunAjaran->label().' dijadikan semester aktif.');
    }

    public function destroy(TahunAjaran $tahunAjaran): RedirectResponse
    {
        if ($tahunAjaran->rombels()->exists()) {
            return back()->with('status', 'Tahun ajaran tidak dapat dihapus karena masih dipakai rombel.');
        }

        if ($tahunAjaran->is_aktif) {
            return back()->with('status', 'Semester aktif tidak dapat dihapus.');
        }

        $tahunAjaran->delete();

        return redirect()
            ->route('tahun-ajaran.index')
            ->with('status', 'Tahun ajaran dihapus.');
    }

    private function validated(Request $request, ?TahunAjaran $tahunAjaran = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:20'],
            'semester' => ['required', Rule::in(['ganjil', 'genap'])],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'is_aktif' => ['sometimes', 'boolean'],
        ]);

        $data['is_aktif'] = $request->boolean('is_aktif');

        $request->validate([
            'nama' => [
                Rule::unique('tahun_ajarans', 'nama')
                    ->where('semester', $data['semester'])
                    ->ignore($tahunAjaran?->id),
            ],
        ]);

        return $data;
    }

    private function jadikanAktif(TahunAjaran $tahunAjaran): void
    {
        DB::transaction(function () use ($tahunAjaran) {
            TahunAjaran::query()->whereKeyNot($tahunAjaran->id)->update(['is_aktif' => false]);
            $tahunAjaran->update(['is_aktif' => true]);
        });
    }
}
