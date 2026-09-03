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
            'tahunAjarans' => TahunAjaran::query()->orderByDesc('tanggal_mulai')->orderByDesc('nama')->get(),
        ]);
    }

    public function create(): View
    {
        return view('tahun-ajaran.form', [
            'tahunAjaran' => new TahunAjaran([
                'tanggal_mulai' => now()->startOfYear()->month(7)->day(13),
                'tanggal_selesai' => now()->addYear()->month(6)->day(12),
                'status' => TahunAjaran::STATUS_BELUM_AKTIF,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        TahunAjaran::query()->create($this->validated($request) + [
            'status' => TahunAjaran::STATUS_BELUM_AKTIF,
            'is_aktif' => false,
        ]);

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

        return redirect()
            ->route('tahun-ajaran.index')
            ->with('status', 'Tahun ajaran diperbarui.');
    }

    public function aktifkan(TahunAjaran $tahunAjaran): RedirectResponse
    {
        $this->jadikanAktif($tahunAjaran);

        return redirect()
            ->route('tahun-ajaran.index')
            ->with('status', $tahunAjaran->label().' dijadikan tahun ajaran aktif.');
    }

    public function destroy(TahunAjaran $tahunAjaran): RedirectResponse
    {
        if (! $tahunAjaran->bisaDihapus()) {
            return back()->with('status', 'Tahun ajaran tidak dapat dihapus karena sudah dipakai data aktif.');
        }

        $tahunAjaran->delete();

        return redirect()
            ->route('tahun-ajaran.index')
            ->with('status', 'Tahun ajaran dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?TahunAjaran $tahunAjaran = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:20', Rule::unique('tahun_ajarans', 'nama')->ignore($tahunAjaran?->id)],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
        ]);
    }

    private function jadikanAktif(TahunAjaran $tahunAjaran): void
    {
        DB::transaction(function () use ($tahunAjaran) {
            TahunAjaran::query()
                ->whereKeyNot($tahunAjaran->id)
                ->where(function ($query) {
                    $query->where('status', TahunAjaran::STATUS_AKTIF)
                        ->orWhere('is_aktif', true);
                })
                ->update([
                    'is_aktif' => false,
                    'status' => TahunAjaran::STATUS_ARSIP,
                ]);

            $tahunAjaran->update([
                'is_aktif' => true,
                'status' => TahunAjaran::STATUS_AKTIF,
            ]);
        });
    }
}
