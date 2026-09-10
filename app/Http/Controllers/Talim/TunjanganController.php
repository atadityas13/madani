<?php

namespace App\Http\Controllers\Talim;

use App\Http\Controllers\Controller;
use App\Models\Gtk;
use App\Models\TahunAjaran;
use App\Models\TunjanganDokumen;
use App\Models\User;
use App\Services\Persuratan\SptjmTpgPdfService;
use App\Services\Tunjangan\TunjanganDokumenService;
use App\Support\TunjanganAkses;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TunjanganController extends Controller
{
    public function __construct(
        private TunjanganDokumenService $dokumen,
        private SptjmTpgPdfService $sptjm,
    ) {}

    public function index(Request $request): View
    {
        $user = $this->user($request);
        if (! $this->bolehAkses($user)) {
            return view('talim.tunjangan.akses-ditolak');
        }

        return view('talim.tunjangan.index', [
            'gtk' => $user->gtk,
            'jenisList' => $this->dokumen->hubJenisList(),
        ]);
    }

    public function show(Request $request, string $jenis): View
    {
        $this->dokumen->assertJenisSemua($jenis);
        $user = $this->user($request);
        if (! $this->bolehAkses($user)) {
            return view('talim.tunjangan.akses-ditolak');
        }

        $gtk = $user->gtk;
        $this->authorize('viewGtk', $gtk);

        $tahunAjaranId = $request->query('tahun_ajaran_id');
        $tahunAjaran = $tahunAjaranId
            ? TahunAjaran::query()->find($tahunAjaranId)
            : TahunAjaran::aktif();

        $dokumens = TunjanganDokumen::query()
            ->where('gtk_id', $gtk->id)
            ->where('jenis', $jenis)
            ->get()
            ->keyBy('slot_key');

        $rows = [];
        if ($jenis === TunjanganDokumen::JENIS_SKAKPT) {
            abort_unless($tahunAjaran, 404, 'Tahun ajaran belum tersedia.');
            foreach (TunjanganDokumenService::grupBulanSkakpt() as $grup) {
                $rows[] = ['type' => 'header', 'label' => $grup['label']];
                foreach ($grup['bulan'] as $bulan => $nama) {
                    $slot = TunjanganDokumen::slotKeySkakpt((int) $tahunAjaran->id, $bulan);
                    $doc = $dokumens->get($slot);
                    $rows[] = [
                        'type' => 'item',
                        'periode' => $bulan,
                        'label' => $nama,
                        'dokumen' => $doc,
                        'boleh_upload' => $this->dokumen->bolehUploadSkakpt($tahunAjaran, $bulan),
                    ];
                }
            }
        } elseif (in_array($jenis, [TunjanganDokumen::JENIS_SKMT, TunjanganDokumen::JENIS_SKBK], true)) {
            abort_unless($tahunAjaran, 404, 'Tahun ajaran belum tersedia.');
            foreach ([1 => 'Semester I (Ganjil)', 2 => 'Semester II (Genap)'] as $sem => $label) {
                $slot = TunjanganDokumen::slotKeySemester($jenis, (int) $tahunAjaran->id, $sem);
                $doc = $dokumens->get($slot);
                $rows[] = [
                    'type' => 'item',
                    'periode' => $sem,
                    'label' => $label,
                    'dokumen' => $doc,
                    'boleh_upload' => $this->dokumen->bolehUploadSemester($tahunAjaran, $sem),
                ];
            }
        }

        return view('talim.tunjangan.show', [
            'jenis' => $jenis,
            'labelJenis' => $this->dokumen->labelJenis($jenis),
            'deskripsiJenis' => $this->dokumen->deskripsiJenis($jenis),
            'gtk' => $gtk,
            'rows' => $rows,
            'tahunAjaran' => $tahunAjaran,
            'tahunAjarans' => TahunAjaran::query()->orderByDesc('tanggal_mulai')->get(),
            'uploadAction' => route('talim.tunjangan.upload', $jenis),
        ]);
    }

    public function upload(Request $request, string $jenis): RedirectResponse
    {
        $this->dokumen->assertJenisUpload($jenis);
        $gtk = $this->gtkSendiri($request);
        $this->authorize('upload', $gtk);

        $validated = $request->validate([
            'periode' => ['required', 'integer'],
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:'.TunjanganDokumenService::MAX_PDF_KB],
        ]);

        $tahunAjaran = TahunAjaran::query()->findOrFail($validated['tahun_ajaran_id']);

        $this->dokumen->simpanPdf(
            $gtk,
            $jenis,
            (int) $validated['periode'],
            $request->file('file'),
            null,
            $tahunAjaran,
        );

        return back()->with('status', 'PDF berhasil diunggah.');
    }

    public function stream(Request $request, string $jenis, TunjanganDokumen $dokumen): StreamedResponse
    {
        return $this->fileResponse($request, $jenis, $dokumen, inline: true);
    }

    public function download(Request $request, string $jenis, TunjanganDokumen $dokumen): StreamedResponse
    {
        return $this->fileResponse($request, $jenis, $dokumen, inline: false);
    }

    public function sptjmDownload(Request $request): Response
    {
        $gtk = $this->gtkSendiri($request);
        $this->authorize('viewGtk', $gtk);

        $validated = $request->validate([
            'tanggal_surat' => ['nullable', 'date'],
            'mode' => ['nullable', 'string', 'in:download,print'],
        ]);

        $tanggal = Carbon::parse($validated['tanggal_surat'] ?? now())
            ->timezone(config('app.timezone'))
            ->locale('id');

        $mode = $validated['mode'] ?? 'download';
        $collection = collect([$gtk]);

        if ($mode === 'print') {
            return $this->sptjm->streamMany($collection, $tanggal);
        }

        return $this->sptjm->downloadMany($collection, $tanggal);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    private function bolehAkses(User $user): bool
    {
        return TunjanganAkses::guruSertifikasi($user);
    }

    private function gtkSendiri(Request $request): Gtk
    {
        $user = $this->user($request);
        abort_unless($this->bolehAkses($user) && $user->gtk !== null, 403);

        return $user->gtk;
    }

    private function fileResponse(Request $request, string $jenis, TunjanganDokumen $dokumen, bool $inline): StreamedResponse
    {
        $this->dokumen->assertJenisUpload($jenis);
        $gtk = $this->gtkSendiri($request);
        abort_unless((int) $dokumen->gtk_id === (int) $gtk->id && $dokumen->jenis === $jenis, 404);
        $this->authorize('viewGtk', $gtk);
        abort_unless(filled($dokumen->path), 404);

        $filename = $dokumen->nama_asli ?: ($this->dokumen->labelJenis($jenis).'.pdf');

        return Storage::disk('r2')->response(
            (string) $dokumen->path,
            $filename,
            ['Content-Type' => 'application/pdf'],
            $inline ? 'inline' : 'attachment',
        );
    }
}
