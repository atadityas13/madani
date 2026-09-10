<?php

namespace App\Http\Controllers\Tunjangan;

use App\Http\Controllers\Controller;
use App\Models\Gtk;
use App\Models\TahunAjaran;
use App\Models\TunjanganDokumen;
use App\Services\Persuratan\SptjmTpgPdfService;
use App\Services\Tunjangan\TunjanganDokumenService;
use App\Services\Tunjangan\TunjanganZipImportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TunjanganController extends Controller
{
    public function __construct(
        private TunjanganDokumenService $dokumen,
        private TunjanganZipImportService $zipImport,
        private SptjmTpgPdfService $sptjm,
    ) {}

    public function index(): View|RedirectResponse
    {
        $this->authorize('aksesModul', TunjanganDokumen::class);

        return view('tunjangan.index', [
            'jenisList' => $this->dokumen->hubJenisList(),
            'isAdmin' => auth()->user()->can('kelolaSemua', TunjanganDokumen::class),
        ]);
    }

    public function jenisIndex(string $jenis): View|RedirectResponse
    {
        $this->dokumen->assertJenisSemua($jenis);
        $this->authorize('aksesModul', TunjanganDokumen::class);

        $user = auth()->user();
        if (! $user->can('kelolaSemua', TunjanganDokumen::class)) {
            $gtk = $user->gtk;
            abort_unless($gtk && $user->can('viewGtk', $gtk), 403);

            return redirect()->route('tunjangan.jenis.show', ['jenis' => $jenis, 'gtk' => $gtk]);
        }

        return view('tunjangan.gtk-list', [
            'jenis' => $jenis,
            'labelJenis' => $this->dokumen->labelJenis($jenis),
            'deskripsiJenis' => $this->dokumen->deskripsiJenis($jenis),
            'gtks' => $this->dokumen->gtkTersertifikasi(),
            'tahunAjarans' => TahunAjaran::query()->orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => TahunAjaran::aktif(),
            'bolehZip' => in_array($jenis, [TunjanganDokumen::JENIS_SKMT, TunjanganDokumen::JENIS_SKBK], true),
        ]);
    }

    public function show(Request $request, string $jenis, Gtk $gtk): View
    {
        $this->dokumen->assertJenisSemua($jenis);
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

        $rows = $this->buildRows($jenis, $tahunAjaran, $dokumens);

        return view('tunjangan.show', [
            'jenis' => $jenis,
            'labelJenis' => $this->dokumen->labelJenis($jenis),
            'deskripsiJenis' => $this->dokumen->deskripsiJenis($jenis),
            'gtk' => $gtk,
            'rows' => $rows,
            'tahunAjaran' => $tahunAjaran,
            'tahunAjarans' => TahunAjaran::query()->orderByDesc('tanggal_mulai')->get(),
            'isAdmin' => auth()->user()->can('kelolaSemua', TunjanganDokumen::class),
            'uploadAction' => route('tunjangan.jenis.upload', [$jenis, $gtk]),
        ]);
    }

    public function upload(Request $request, string $jenis, Gtk $gtk): RedirectResponse
    {
        $this->dokumen->assertJenisUpload($jenis);
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

    public function stream(string $jenis, Gtk $gtk, TunjanganDokumen $dokumen): StreamedResponse
    {
        return $this->fileResponse($jenis, $gtk, $dokumen, inline: true);
    }

    public function download(string $jenis, Gtk $gtk, TunjanganDokumen $dokumen): StreamedResponse
    {
        return $this->fileResponse($jenis, $gtk, $dokumen, inline: false);
    }

    public function uploadZip(Request $request, string $jenis): RedirectResponse
    {
        $this->dokumen->assertJenisUpload($jenis);
        abort_unless(in_array($jenis, [TunjanganDokumen::JENIS_SKMT, TunjanganDokumen::JENIS_SKBK], true), 404);
        $this->authorize('uploadZip', TunjanganDokumen::class);

        $rules = [
            'zip' => ['required', 'file', 'mimes:zip', 'max:51200'],
        ];
        if ($jenis === TunjanganDokumen::JENIS_SKBK) {
            $rules['tahun_ajaran_id'] = ['required', 'integer', 'exists:tahun_ajarans,id'];
            $rules['semester'] = ['required', 'integer', 'in:1,2'];
        }

        $validated = $request->validate($rules);
        $ta = isset($validated['tahun_ajaran_id'])
            ? TahunAjaran::query()->findOrFail($validated['tahun_ajaran_id'])
            : null;

        $hasil = $this->zipImport->impor(
            $jenis,
            $request->file('zip'),
            $ta,
            isset($validated['semester']) ? (int) $validated['semester'] : null,
        );

        return redirect()
            ->route('tunjangan.jenis.index', $jenis)
            ->with('status', $hasil['imported'].' file berhasil diimpor.')
            ->with('tunjangan_zip_hasil', $hasil);
    }

    public function sptjmDownload(Request $request, Gtk $gtk): Response
    {
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

    /**
     * @param  Collection<string, TunjanganDokumen>  $dokumens
     * @return list<array<string, mixed>>
     */
    private function buildRows(string $jenis, ?TahunAjaran $tahunAjaran, $dokumens): array
    {
        $rows = [];

        if ($jenis === TunjanganDokumen::JENIS_SKAKPT) {
            abort_unless($tahunAjaran, 404, 'Tahun ajaran belum tersedia.');
            foreach (TunjanganDokumenService::grupBulanSkakpt() as $grup) {
                $rows[] = [
                    'type' => 'header',
                    'label' => $grup['label'],
                ];
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

            return $rows;
        }

        if (in_array($jenis, [TunjanganDokumen::JENIS_SKMT, TunjanganDokumen::JENIS_SKBK], true)) {
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

        return $rows;
    }

    private function fileResponse(string $jenis, Gtk $gtk, TunjanganDokumen $dokumen, bool $inline): StreamedResponse
    {
        $this->dokumen->assertJenisUpload($jenis);
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
