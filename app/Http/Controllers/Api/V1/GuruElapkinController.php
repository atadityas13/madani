<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ElapkinBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuruElapkinController extends Controller
{
    public function __construct(private ElapkinBridge $elapkin) {}

    public function ssoToken(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $ticket = $this->elapkin->buildSsoTicket($user);

        return response()->json([
            'success' => true,
            ...$ticket,
            'expires_in' => 300,
        ]);
    }

    public function bridgeSession(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $bridge = $this->elapkin->openSession($user);
        if (! $bridge['success']) {
            return response()->json([
                'success' => false,
                'message' => $bridge['message'] ?? 'Gagal membuka sesi Kinerja.',
            ], $bridge['status'] ?? 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesi Kinerja berhasil dibuka.',
            'cookies' => $bridge['cookies'],
            'kepala_madrasah' => $bridge['kepala_madrasah'],
        ]);
    }

    public function hariLibur(Request $request): JsonResponse
    {
        $tahun = (int) $request->query('tahun', now()->year);
        if ($tahun < 2000 || $tahun > 2100) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tahun tidak valid.',
            ], 422);
        }

        /** @var User $user */
        $user = $request->user();
        $result = $this->elapkin->hariLibur($user, $tahun);
        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Gagal memuat hari libur.',
            ], $result['status'] ?? 502);
        }

        return response()->json([
            'success' => true,
            'tahun' => $result['tahun'],
            'data' => $result['data'] ?? [],
            'count' => $result['count'] ?? 0,
        ]);
    }
}
