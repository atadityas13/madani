<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiswaController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $siswas = Siswa::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%")
                        ->orWhere('nik', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('siswa.index', compact('siswas', 'q'));
    }

    public function create(): View
    {
        return view('siswa.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nisn' => ['nullable', 'digits:10', 'unique:siswas,nisn'],
            'nik' => ['nullable', 'digits:16', 'unique:siswas,nik'],
            'nama' => ['required', 'string', 'max:255'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'agama' => ['nullable', 'string', 'max:30'],
            'no_hp' => ['nullable', 'string', 'max:20'],
        ]);

        $siswa = Siswa::query()->create($data + [
            'kewarganegaraan' => 'WNI',
            'status_keaktifan' => 'aktif_tanpa_rombel',
        ]);

        foreach (['ayah', 'ibu', 'wali'] as $peran) {
            $siswa->orangTuas()->create(['peran' => $peran]);
        }

        if ($tahun = TahunAjaran::aktif()) {
            $siswa->periodiks()->create(['tahun_ajaran_id' => $tahun->id]);
        }

        return redirect()->route('siswa.show', $siswa)->with('status', 'Siswa berhasil dicatat.');
    }

    public function show(Siswa $siswa): View
    {
        $siswa->load(['orangTuas', 'periodiks.tahunAjaran', 'rombels.tahunAjaran', 'beasiswas', 'prestasis']);

        return view('siswa.show', compact('siswa'));
    }
}
