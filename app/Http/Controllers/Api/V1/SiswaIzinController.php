<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IzinSiswa;
use App\Models\Siswa;
use App\Services\IzinSiswaService;
use App\Services\SuratIzinSiswaPdfService;
use App\Support\PernyataanIzinSiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class SiswaIzinController extends Controller
{
    public function __construct(
        private IzinSiswaService $service,
        private SuratIzinSiswaPdfService $suratPdf,
    ) {}

    public function meta(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'teks_pernyataan_izin' => PernyataanIzinSiswa::payload(),
                'jenis' => IzinSiswa::JENIS_LABEL,
                'tanggal_min' => now()->subDay()->toDateString(),
                'tanggal_max' => now()->addDays(7)->toDateString(),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        /** @var Siswa $siswa */
        $siswa = $request->user();

        $items = IzinSiswa::query()
            ->with('rombel:id,tingkat,nama')
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->limit(60)
            ->get()
            ->map(fn (IzinSiswa $izin) => $this->service->toSiswaItem($izin))
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $items,
            'teks_pernyataan_izin' => PernyataanIzinSiswa::payload(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Siswa $siswa */
        $siswa = $request->user();

        $data = $request->validate([
            'jenis' => ['required', Rule::in([IzinSiswa::JENIS_IZIN, IzinSiswa::JENIS_SAKIT])],
            'tanggal' => ['required', 'date'],
            'alasan' => ['required', 'string', 'min:5', 'max:1000'],
            'pernyataan_disetujui' => ['required', 'boolean'],
            'ttd_wali' => ['required', 'string'],
            'lampiran' => ['nullable', 'string'],
            'jenis_bukti' => ['nullable', 'string', 'min:3', 'max:200', 'required_with:lampiran'],
        ]);

        $izin = $this->service->simpan(
            $siswa,
            $data,
            $data['ttd_wali'],
            $data['lampiran'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan izin/sakit berhasil dikirim.',
            'data' => $this->service->toSiswaItem($izin),
        ], 201);
    }

    public function show(Request $request, IzinSiswa $izin): JsonResponse
    {
        /** @var Siswa $siswa */
        $siswa = $request->user();

        if ($izin->siswa_id !== $siswa->id) {
            abort(404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->service->toSiswaItem($izin),
        ]);
    }

    public function suratPdf(Request $request, IzinSiswa $izin): Response
    {
        /** @var Siswa $siswa */
        $siswa = $request->user();

        if ($izin->siswa_id !== $siswa->id || ! $izin->punyaSuratOrtu()) {
            abort(404);
        }

        $download = $request->boolean('download');

        return $download
            ? $this->suratPdf->download($izin)
            : $this->suratPdf->stream($izin);
    }
}
