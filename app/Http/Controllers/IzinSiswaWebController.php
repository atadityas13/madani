<?php

namespace App\Http\Controllers;

use App\Models\IzinSiswa;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\IzinSiswaService;
use App\Services\SuratIzinSiswaPdfService;
use App\Services\WaliKelasDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class IzinSiswaWebController extends Controller
{
    public function __construct(
        private IzinSiswaService $service,
        private SuratIzinSiswaPdfService $suratPdf,
        private WaliKelasDashboardService $waliDashboard,
    ) {}

    public function rekap(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $webview = $request->routeIs('talim.wali.*');

        if (! $this->bolehAkses($user)) {
            return $webview
                ? view('talim.wali.akses-ditolak')
                : abort(403);
        }

        $this->authorize('viewAny', IzinSiswa::class);

        $tanggal = $this->tanggalFilter($request);
        $rombelIds = $this->rombelIdsTerbatas($user);
        $rekap = $this->service->rekapSiaPerKelas($tanggal, $rombelIds);
        $rincian = $this->service->daftarHariIni($tanggal, $rombelIds);
        $rombelWali = $user->bisaKelola() ? null : $this->waliDashboard->rombelWali($user);

        return view('izin.rekap', [
            'layout' => $webview ? 'layouts.talim-webview' : 'layouts.app',
            'webview' => $webview,
            'kembali_url' => $webview ? route('talim.wali') : null,
            'tanggal' => $tanggal->toDateString(),
            'rekap' => $rekap,
            'rincian' => $rincian,
            'rombel_wali' => $rombelWali,
            'tahun_label' => TahunAjaran::aktif()?->label(),
            'admin' => $user->bisaKelola(),
        ]);
    }

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $webview = $request->routeIs('talim.wali.*');

        if (! $this->bolehAkses($user)) {
            return $webview
                ? view('talim.wali.akses-ditolak')
                : abort(403);
        }

        $this->authorize('viewAny', IzinSiswa::class);

        $tanggal = $this->tanggalFilter($request);
        $rombelIds = $this->rombelIdsTerbatas($user);
        $jenis = (string) $request->query('jenis', '');
        if (! in_array($jenis, [IzinSiswa::JENIS_IZIN, IzinSiswa::JENIS_SAKIT], true)) {
            $jenis = '';
        }

        $rombelId = $request->integer('rombel_id') ?: null;
        if ($rombelIds !== null && $rombelId !== null && ! in_array($rombelId, $rombelIds, true)) {
            $rombelId = null;
        }

        $q = trim((string) $request->query('q', ''));
        $items = $this->service->paginasiSurat(
            $tanggal,
            $jenis !== '' ? $jenis : null,
            $rombelId,
            $q !== '' ? $q : null,
            $rombelIds,
        );

        $rombels = $user->bisaKelola()
            ? Rombel::query()
                ->when(TahunAjaran::aktif(), fn ($query) => $query->where('tahun_ajaran_id', TahunAjaran::aktif()->id))
                ->ordered()
                ->get()
            : collect();

        $rombelWali = $user->bisaKelola() ? null : $this->waliDashboard->rombelWali($user);

        return view('izin.index', [
            'layout' => $webview ? 'layouts.talim-webview' : 'layouts.app',
            'webview' => $webview,
            'kembali_url' => $webview ? route('talim.wali') : null,
            'tanggal' => $tanggal->toDateString(),
            'jenis' => $jenis,
            'rombelId' => $rombelId,
            'q' => $q,
            'items' => $items,
            'rombels' => $rombels,
            'rombel_wali' => $rombelWali,
            'tahun_label' => TahunAjaran::aktif()?->label(),
            'admin' => $user->bisaKelola(),
            'surat_route' => $webview ? 'talim.wali.izin.surat' : 'izin-siswa.surat',
        ]);
    }

    public function surat(Request $request, IzinSiswa $izin): Response
    {
        $this->authorize('view', $izin);

        abort_unless($izin->punyaSuratOrtu(), 404);

        return $request->boolean('download')
            ? $this->suratPdf->download($izin)
            : $this->suratPdf->stream($izin);
    }

    private function bolehAkses(User $user): bool
    {
        return $user->bisaKelola() || $user->adalahWali();
    }

    /**
     * @return list<int>|null
     */
    private function rombelIdsTerbatas(User $user): ?array
    {
        if ($user->bisaKelola()) {
            return null;
        }

        return $user->rombelIdsAktif();
    }

    private function tanggalFilter(Request $request): Carbon
    {
        $raw = trim((string) $request->query('tanggal', ''));
        if ($raw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            return Carbon::parse($raw)->startOfDay();
        }

        return now()->startOfDay();
    }
}
