<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IzinSiswa;
use App\Models\User;
use App\Services\IzinSiswaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuruIzinController extends Controller
{
    public function __construct(private IzinSiswaService $service) {}

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

        $izin = $this->service->batalkanOlehWali(
            $izin,
            $user,
            $data['alasan_batal'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan dibatalkan.',
            'data' => $this->service->toGuruItem($izin, $user->gtk_id),
        ]);
    }
}
