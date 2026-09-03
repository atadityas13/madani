<?php

namespace App\Http\Controllers;

use App\Models\PengajuanPerubahanSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\SiswaBiodataService;
use App\Support\KelengkapanSiswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiswaController extends Controller
{
    public function __construct(private SiswaBiodataService $biodata) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Siswa::class);

        $q = trim((string) $request->query('q', ''));
        $user = auth()->user();

        $siswas = Siswa::query()
            ->with(['rombels' => function ($query) {
                $query->wherePivot('status', 'aktif')
                    ->when(TahunAjaran::aktif(), fn ($rombel) => $rombel->where('tahun_ajaran_id', TahunAjaran::aktif()->id));
            }])
            ->when($user?->adalahWali(), function ($query) use ($user) {
                $ids = $user->rombelIdsAktif();
                if ($ids === []) {
                    $query->whereRaw('0 = 1');

                    return;
                }

                $query->whereHas('rombels', fn ($inner) => $inner
                    ->whereIn('rombels.id', $ids)
                    ->where('rombel_siswas.status', 'aktif'));
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%")
                        ->orWhere('nik', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('siswa.index', compact('siswas', 'q'));
    }

    public function create(): View
    {
        $this->authorize('create', Siswa::class);

        return view('siswa.create', [
            'emis' => config('emis'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Siswa::class);

        $siswa = $this->biodata->create($request);

        return redirect()
            ->route('siswa.show', $siswa)
            ->with('status', 'Siswa berhasil dicatat. Lengkapi tab lain mengikuti EMIS 4.0.');
    }

    public function show(Siswa $siswa): View|RedirectResponse
    {
        $this->authorize('view', $siswa);

        $this->biodata->ensureRelasi($siswa);

        if (request('tab') === 'kebutuhan-khusus') {
            return redirect()->route('siswa.show', ['siswa' => $siswa, 'tab' => 'data-siswa']);
        }

        $siswa->load([
            'orangTuas', 'periodiks.tahunAjaran', 'rombels.tahunAjaran',
            'beasiswas', 'prestasis', 'rekamDidik', 'dokumens', 'ayah', 'ibu',
            'pengajuanPerubahans',
        ]);

        $diminta = request('tab');
        $diminta = is_string($diminta) && $diminta !== '' ? $diminta : 'data-siswa';
        $tab = KelengkapanSiswa::tabDikenal($siswa, $diminta);

        if ($tab !== $diminta) {
            return redirect()->route('siswa.show', ['siswa' => $siswa, 'tab' => $tab]);
        }

        return view('siswa.show', [
            'siswa' => $siswa,
            'periodik' => $siswa->periodikAktif(),
            'emis' => config('emis'),
            'tab' => $tab,
            'navigasi' => KelengkapanSiswa::navigasi($siswa, $tab),
            'alamatOrtu' => $this->biodata->alamatOrtuUtama($siswa),
            'alamatAsrama' => config('emis.asrama_madrasah'),
            'portal' => false,
        ]);
    }

    public function edit(Siswa $siswa): RedirectResponse
    {
        $this->authorize('view', $siswa);

        return redirect()->route('siswa.show', ['siswa' => $siswa, 'tab' => 'data-siswa']);
    }

    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $this->authorize('update', $siswa);

        $bagian = (string) $request->input('bagian', 'data-siswa');
        $pesan = $this->biodata->updateBagian($request, $siswa, $bagian);
        $tab = in_array($bagian, ['orang-tua', 'alamat', 'aktivitas', 'beasiswa', 'prestasi', 'rekam-didik'], true)
            ? $bagian
            : 'data-siswa';

        return redirect()
            ->route('siswa.show', ['siswa' => $siswa, 'tab' => $tab])
            ->with('status', $pesan);
    }

    public function prosesPengajuan(Request $request, Siswa $siswa, PengajuanPerubahanSiswa $pengajuan): RedirectResponse
    {
        $this->authorize('update', $siswa);
        $aksi = (string) $request->input('aksi', 'terima');
        $pesan = $this->biodata->prosesPengajuan($siswa, $pengajuan, $aksi);

        return redirect()
            ->route('siswa.show', ['siswa' => $siswa, 'tab' => 'data-siswa'])
            ->with('status', $pesan);
    }

    public function destroyRelasi(Request $request, Siswa $siswa): RedirectResponse
    {
        $this->authorize('update', $siswa);
        $jenis = (string) $request->input('jenis');
        $id = (int) $request->input('id');
        $tab = $this->biodata->hapusRelasi($siswa, $jenis, $id);

        return redirect()
            ->route('siswa.show', ['siswa' => $siswa, 'tab' => $tab])
            ->with('status', 'Data dihapus.');
    }

    public function resetPassword(Siswa $siswa): RedirectResponse
    {
        $this->authorize('update', $siswa);

        if (! $siswa->resetPasswordAwal()) {
            return back()->withErrors([
                'password' => 'Password tidak bisa direset. Isi tanggal lahir siswa terlebih dahulu.',
            ]);
        }

        return back()->with('status', 'Password direset ke tanggal lahir (ddmmyyyy). Siswa wajib mengubahnya saat masuk.');
    }
}
