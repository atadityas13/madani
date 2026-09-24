<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IzinSiswa;
use App\Models\Rombel;
use App\Models\User;
use App\Services\IzinSiswaService;
use App\Services\SuratIzinSiswaPdfService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuruIzinController extends Controller
{
    public function __construct(
        private IzinSiswaService $service,
        private SuratIzinSiswaPdfService $suratPdf,
    ) {}

    public function hariIni(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $rekap = $this->service->rekapHariIni($user);

        return response()->json([
            'success' => true,
            'data' => $rekap,
        ]);
    }

    public function batalkan(Request $request, IzinSiswa $izin): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'alasan_batal' => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->batalkanOlehWali(
            $izin,
            $user,
            $data['alasan_batal'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan ketidakhadiran dihapus.',
        ]);
    }

    public function suratPdf(Request $request, IzinSiswa $izin): Response
    {
        if (! $izin->punyaSuratOrtu()) {
            abort(404);
        }

        $download = $request->boolean('download');

        return $download
            ? $this->suratPdf->download($izin)
            : $this->suratPdf->stream($izin);
    }

    public function rombels(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->daftarRombelAktif(),
        ]);
    }

    public function siswaRombel(Request $request, Rombel $rombel): JsonResponse
    {
        $data = $request->validate([
            'tanggal' => ['nullable', 'date'],
        ]);

        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'rombel' => [
                    'id' => $rombel->id,
                    'label' => $rombel->label(),
                ],
                'siswa' => $this->service->daftarSiswaRombel($rombel->id, $tanggal),
            ],
        ]);
    }

    public function storeAlpa(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'rombel_id' => ['required', 'integer', 'exists:rombels,id'],
            'siswa_ids' => ['required', 'array', 'min:1'],
            'siswa_ids.*' => ['required', 'uuid', 'exists:siswas,id'],
            'tanggal' => ['nullable', 'date'],
        ]);

        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : null;
        $result = $this->service->laporkanAlpa(
            $user,
            (int) $data['rombel_id'],
            $data['siswa_ids'],
            $tanggal,
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan alpa berhasil disimpan.',
            'data' => $result,
        ], 201);
    }

    public function rekapSia(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tanggal' => ['nullable', 'date'],
        ]);

        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : null;

        return response()->json([
            'success' => true,
            'data' => $this->service->rekapSiaPerKelas($tanggal),
        ]);
    }
}
