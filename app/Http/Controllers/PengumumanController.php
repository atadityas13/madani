<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use App\Support\UkuranHalaman;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PengumumanController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $hasil = UkuranHalaman::paginateQuery(
                Pengumuman::query()->orderByDesc('published_at')->orderByDesc('id'),
                $request,
                UkuranHalaman::STANDAR,
                15,
            );
            $items = $hasil['baris'];
            $perPage = $hasil['label'];
        } catch (QueryException) {
            $items = UkuranHalaman::kosong($request);
            $perPage = '15';
            session()->flash('error', 'Tabel pengumuman belum tersedia. Jalankan: php artisan migrate');
        }

        return view('pengumuman.index', compact('items', 'perPage'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('pengumumans')) {
            return redirect()
                ->route('pengumuman.index')
                ->with('error', 'Tabel pengumuman belum tersedia. Jalankan: php artisan migrate');
        }

        $data = $request->validate([
            'judul' => 'required|string|max:200',
            'isi' => 'required|string|max:5000',
            'is_active' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        Pengumuman::create([
            'judul' => $data['judul'],
            'isi' => $data['isi'],
            'is_active' => $request->boolean('is_active', true),
            'published_at' => $data['published_at'] ?? now(),
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman berhasil dikirim.');
    }

    public function update(Request $request, Pengumuman $pengumuman): RedirectResponse
    {
        $data = $request->validate([
            'judul' => 'required|string|max:200',
            'isi' => 'required|string|max:5000',
            'is_active' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        $pengumuman->update([
            'judul' => $data['judul'],
            'isi' => $data['isi'],
            'is_active' => $request->boolean('is_active'),
            'published_at' => $data['published_at'] ?? $pengumuman->published_at,
        ]);

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman diperbarui.');
    }

    public function destroy(Pengumuman $pengumuman): RedirectResponse
    {
        $pengumuman->delete();

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman dihapus.');
    }
}
