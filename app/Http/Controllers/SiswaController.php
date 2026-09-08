<?php

namespace App\Http\Controllers;

use App\Models\PengajuanPerubahanSiswa;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\KartuEPelajarPdfService;
use App\Services\KartuEPelajarService;
use App\Services\PernyataanPdfService;
use App\Services\PortofolioPdfService;
use App\Services\SiswaBiodataService;
use App\Services\SiswaNisGeneratorService;
use App\Services\SiswaPernyataanService;
use App\Support\KelengkapanSiswa;
use App\Support\R2Url;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiswaController extends Controller
{
    public function __construct(
        private SiswaBiodataService $biodata,
        private SiswaNisGeneratorService $nisGenerator,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Siswa::class);

        $q = trim((string) $request->query('q', ''));
        $tingkat = trim((string) $request->query('tingkat', ''));
        $rombelId = trim((string) $request->query('rombel_id', ''));
        $perPageRaw = (string) $request->query('per_page', '10');
        $user = auth()->user();
        $tahun = TahunAjaran::aktif();

        $rombels = Rombel::query()
            ->when($tahun, fn ($query) => $query->where('tahun_ajaran_id', $tahun->id))
            ->when($user?->adalahWali(), function ($query) use ($user) {
                $ids = $user->rombelIdsAktif();
                $query->whereIn('id', $ids === [] ? [0] : $ids);
            })
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->get();

        $tingkatOptions = $rombels
            ->pluck('tingkat')
            ->filter(fn ($item) => filled($item))
            ->unique()
            ->sortBy(fn ($item) => Rombel::tingkatOrder($item))
            ->values();

        if ($tingkat !== '' && ! $tingkatOptions->contains($tingkat)) {
            $tingkat = '';
        }

        $rombelsForSelect = $tingkat !== ''
            ? $rombels->where('tingkat', $tingkat)->values()
            : $rombels->values();

        if ($rombelId !== '' && ! $rombelsForSelect->contains(fn (Rombel $rombel) => (string) $rombel->id === $rombelId)) {
            $rombelId = '';
        }

        $query = Siswa::query()
            ->with([
                'pernyataan',
                'rombels' => function ($rombelQuery) use ($tahun) {
                    $rombelQuery->wherePivot('status', 'aktif')
                        ->when($tahun, fn ($inner) => $inner->where('tahun_ajaran_id', $tahun->id));
                },
            ])
            ->when($user?->adalahWali(), function ($siswaQuery) use ($user) {
                $ids = $user->rombelIdsAktif();
                if ($ids === []) {
                    $siswaQuery->whereRaw('0 = 1');

                    return;
                }

                $siswaQuery->whereHas('rombels', fn ($inner) => $inner
                    ->whereIn('rombels.id', $ids)
                    ->where('rombel_siswas.status', 'aktif'));
            })
            ->when($q !== '', function ($siswaQuery) use ($q) {
                $siswaQuery->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%")
                        ->orWhere('nik', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%");
                });
            })
            ->when($tingkat !== '', function ($siswaQuery) use ($tingkat, $tahun) {
                $siswaQuery->whereHas('rombels', fn ($inner) => $inner
                    ->where('tingkat', $tingkat)
                    ->where('rombel_siswas.status', 'aktif')
                    ->when($tahun, fn ($rombel) => $rombel->where('tahun_ajaran_id', $tahun->id)));
            })
            ->when($rombelId !== '', function ($siswaQuery) use ($rombelId) {
                $siswaQuery->whereHas('rombels', fn ($inner) => $inner
                    ->where('rombels.id', $rombelId)
                    ->where('rombel_siswas.status', 'aktif'));
            });

        $rombelUrut = DB::table('rombel_siswas')
            ->join('rombels', 'rombels.id', '=', 'rombel_siswas.rombel_id')
            ->where('rombel_siswas.status', 'aktif')
            ->when($tahun, fn ($join) => $join->where('rombels.tahun_ajaran_id', $tahun->id))
            ->select('rombel_siswas.siswa_id', 'rombels.nama as rombel_nama');

        $query->leftJoinSub($rombelUrut, 'rombel_urut', 'rombel_urut.siswa_id', '=', 'siswas.id')
            ->select('siswas.*')
            ->orderByRaw("CASE siswas.angkatan WHEN 'VII' THEN 1 WHEN 'VIII' THEN 2 WHEN 'IX' THEN 3 ELSE 9 END")
            ->orderByRaw('CAST(rombel_urut.rombel_nama AS UNSIGNED)')
            ->orderBy('rombel_urut.rombel_nama')
            ->orderBy('siswas.nama');

        $allowedPerPage = [10, 20, 50, 100];
        if ($perPageRaw === 'all') {
            $perPage = max((clone $query)->count(), 1);
            $perPageLabel = 'all';
        } else {
            $perPage = in_array((int) $perPageRaw, $allowedPerPage, true) ? (int) $perPageRaw : 10;
            $perPageLabel = (string) $perPage;
        }

        $siswas = $query->paginate($perPage)->withQueryString();

        return view('siswa.index', [
            'siswas' => $siswas,
            'q' => $q,
            'tingkat' => $tingkat,
            'rombelId' => $rombelId,
            'perPage' => $perPageLabel,
            'tingkatOptions' => $tingkatOptions,
            'rombels' => $rombelsForSelect,
            'jumlahTanpaNis' => $this->nisGenerator->jumlahTanpaNis(),
            'jumlahTanpaNisPerAngkatan' => $this->nisGenerator->jumlahTanpaNisPerAngkatan(),
            'bisaGenerateNis' => auth()->user()?->can('create', Siswa::class) ?? false,
        ]);
    }

    public function generateNis(Request $request): RedirectResponse
    {
        $this->authorize('create', Siswa::class);

        $data = $request->validate([
            'angkatan' => ['required', 'string', 'in:VII,VIII,IX'],
        ], [
            'angkatan.required' => 'Angkatan wajib dipilih.',
            'angkatan.in' => 'Angkatan tidak valid.',
        ]);

        $hasil = $this->nisGenerator->generateUntukAngkatan($data['angkatan']);

        return redirect()
            ->route('siswa.index')
            ->with('status', $hasil['pesan']);
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
            ->route('siswa.edit', $siswa)
            ->with('status', 'Siswa berhasil dicatat. Lengkapi tab lain mengikuti EMIS 4.0.');
    }

    public function show(Siswa $siswa): View|RedirectResponse
    {
        $this->authorize('view', $siswa);

        if (request()->filled('tab')) {
            return redirect()->route('siswa.edit', [
                'siswa' => $siswa,
                'tab' => request('tab'),
            ]);
        }

        $siswa->load([
            'periodiks.tahunAjaran',
            'rombels.tahunAjaran',
            'dokumens',
            'pernyataan',
            'ayah',
            'ibu',
            'wali',
            'rekamDidik',
            'prestasis',
            'beasiswas',
        ]);

        $periodik = $siswa->periodikAktif();
        $rombel = $siswa->rombels
            ->first(fn ($item) => $item->pivot?->status === 'aktif')
            ?? $siswa->rombels->first();

        return view('siswa.detail', [
            'siswa' => $siswa,
            'periodik' => $periodik,
            'rombel' => $rombel,
            'fotoUrl' => R2Url::readable($siswa->foto),
        ]);
    }

    public function edit(Siswa $siswa): View|RedirectResponse
    {
        $this->authorize('update', $siswa);

        $this->biodata->ensureRelasi($siswa);

        if (request('tab') === 'kebutuhan-khusus') {
            return redirect()->route('siswa.edit', ['siswa' => $siswa, 'tab' => 'data-siswa']);
        }

        $siswa->load([
            'orangTuas', 'periodiks.tahunAjaran', 'rombels.tahunAjaran',
            'beasiswas', 'prestasis', 'rekamDidik', 'dokumens', 'ayah', 'ibu',
            'pengajuanPerubahans', 'pernyataan',
        ]);

        $diminta = request('tab');
        $diminta = is_string($diminta) && $diminta !== '' ? $diminta : 'data-siswa';
        $tab = KelengkapanSiswa::tabDikenal($siswa, $diminta);

        if ($tab !== $diminta) {
            return redirect()->route('siswa.edit', ['siswa' => $siswa, 'tab' => $tab]);
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

    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $this->authorize('update', $siswa);

        $bagian = (string) $request->input('bagian', 'data-siswa');
        $pesan = $this->biodata->updateBagian($request, $siswa, $bagian);
        $tab = in_array($bagian, ['orang-tua', 'alamat', 'aktivitas', 'beasiswa', 'prestasi', 'rekam-didik'], true)
            ? $bagian
            : 'data-siswa';

        return redirect()
            ->route('siswa.edit', ['siswa' => $siswa, 'tab' => $tab])
            ->with('status', $pesan);
    }

    public function prosesPengajuan(Request $request, Siswa $siswa, PengajuanPerubahanSiswa $pengajuan): RedirectResponse
    {
        $this->authorize('update', $siswa);
        $aksi = (string) $request->input('aksi', 'terima');
        $pesan = $this->biodata->prosesPengajuan($siswa, $pengajuan, $aksi);

        return redirect()
            ->route('siswa.edit', ['siswa' => $siswa, 'tab' => 'data-siswa'])
            ->with('status', $pesan);
    }

    public function destroyRelasi(Request $request, Siswa $siswa): RedirectResponse
    {
        $this->authorize('update', $siswa);
        $jenis = (string) $request->input('jenis');
        $id = (int) $request->input('id');
        $tab = $this->biodata->hapusRelasi($siswa, $jenis, $id);

        return redirect()
            ->route('siswa.edit', ['siswa' => $siswa, 'tab' => $tab])
            ->with('status', 'Data dihapus.');
    }

    public function destroyDokumen(Siswa $siswa, string $jenis): RedirectResponse
    {
        $this->authorize('update', $siswa);

        if (! in_array($jenis, ['kk', 'akta_lahir', 'kip', 'kks', 'pkh', 'ijazah_sd'], true)) {
            abort(404);
        }

        $this->biodata->hapusDokumen($siswa, $jenis);

        $tab = match ($jenis) {
            'kks', 'pkh' => 'orang-tua',
            'ijazah_sd' => 'rekam-didik',
            default => 'data-siswa',
        };

        return redirect()
            ->route('siswa.edit', ['siswa' => $siswa, 'tab' => $tab])
            ->with('status', 'Dokumen dihapus dari database dan storage.');
    }

    public function downloadDokumen(Siswa $siswa, string $jenis): StreamedResponse
    {
        $this->authorize('view', $siswa);

        $label = match ($jenis) {
            'kk' => 'KK',
            'akta_lahir' => 'Akta',
            'kip' => 'KIP',
            'kks' => 'KKS',
            'pkh' => 'PKH',
            'ijazah_sd' => 'IjazahSD',
            default => abort(404),
        };

        $dokumen = $siswa->dokumenJenis($jenis);
        abort_unless($dokumen && filled($dokumen->path), 404);

        $extension = pathinfo((string) $dokumen->path, PATHINFO_EXTENSION)
            ?: pathinfo((string) $dokumen->nama_asli, PATHINFO_EXTENSION)
            ?: 'bin';

        $basename = preg_replace('/[\\\\\\/:*?"<>|]+/', ' ', $siswa->nama) ?: 'Siswa';
        $basename = trim(preg_replace('/\\s+/', ' ', $basename) ?? 'Siswa');
        $filename = "{$basename}_{$label}.{$extension}";

        return Storage::disk('r2')->download((string) $dokumen->path, $filename);
    }

    public function downloadFoto(Siswa $siswa): StreamedResponse
    {
        $this->authorize('view', $siswa);
        abort_unless(filled($siswa->foto), 404);

        $extension = pathinfo((string) $siswa->foto, PATHINFO_EXTENSION) ?: 'jpg';
        $basename = preg_replace('/[\\\\\\/:*?"<>|]+/', ' ', $siswa->nama) ?: 'Siswa';
        $basename = trim(preg_replace('/\\s+/', ' ', $basename) ?? 'Siswa');
        $filename = "{$basename}_Foto.{$extension}";

        return Storage::disk('r2')->download((string) $siswa->foto, $filename);
    }

    public function portofolio(Siswa $siswa): View
    {
        $this->authorize('view', $siswa);

        return view('siswa.portofolio-preview', [
            'siswa' => $siswa,
        ]);
    }

    public function portofolioStream(Siswa $siswa, PortofolioPdfService $portofolio): Response
    {
        $this->authorize('view', $siswa);

        return $portofolio->stream($siswa);
    }

    public function portofolioDownload(Siswa $siswa, PortofolioPdfService $portofolio): Response
    {
        $this->authorize('view', $siswa);

        return $portofolio->download($siswa);
    }

    public function pernyataanDownload(Siswa $siswa, string $jenis, PernyataanPdfService $pdf): Response
    {
        $this->authorize('view', $siswa);
        $item = $siswa->pernyataan;
        abort_unless($item, 404);
        abort_unless(in_array($jenis, PernyataanPdfService::JENIS_VALID, true), 404);

        return $pdf->downloadSaved($item, $jenis);
    }

    public function pernyataanStream(Siswa $siswa, string $jenis, PernyataanPdfService $pdf): Response
    {
        $this->authorize('view', $siswa);
        $item = $siswa->pernyataan;
        abort_unless($item, 404);
        abort_unless(in_array($jenis, PernyataanPdfService::JENIS_VALID, true), 404);

        return $pdf->streamSaved($item, $jenis);
    }

    public function batalkanPernyataan(Siswa $siswa, SiswaPernyataanService $pernyataan): RedirectResponse
    {
        $this->authorize('update', $siswa);
        abort_unless($siswa->pernyataan, 404);

        $pernyataan->batalkan($siswa);

        return redirect()
            ->route('siswa.index')
            ->with('status', 'Konfirmasi pernyataan dibatalkan. Siswa dapat mengedit data kembali selama periode pendataan terbuka.');
    }

    public function kartu(Siswa $siswa): View
    {
        $this->authorize('view', $siswa);

        return view('siswa.kartu-e-pelajar-preview', [
            'siswa' => $siswa,
        ]);
    }

    public function kartuStream(Siswa $siswa, KartuEPelajarPdfService $kartuPdf): Response
    {
        $this->authorize('view', $siswa);

        return $kartuPdf->stream($siswa)->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function kartuDownload(Siswa $siswa, KartuEPelajarPdfService $kartuPdf): Response
    {
        $this->authorize('view', $siswa);

        return $kartuPdf->download($siswa);
    }

    public function cekPortofolio(Siswa $siswa): View
    {
        return view('siswa.portofolio-cek', [
            'siswa' => $siswa,
        ]);
    }

    public function cekKartuEPelajar(Siswa $siswa, KartuEPelajarService $kartu): View
    {
        return view('siswa.kartu-e-pelajar-cek', [
            'siswa' => $siswa,
            'kartu' => $kartu->payload($siswa),
        ]);
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
