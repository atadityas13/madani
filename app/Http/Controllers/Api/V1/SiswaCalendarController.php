<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ElapkinBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiswaCalendarController extends Controller
{
    public function __construct(private ElapkinBridge $elapkin) {}

    public function calendarEvents(Request $request, GuruCalendarEventController $events): JsonResponse
    {
        return $events->index($request);
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

        $result = $this->elapkin->hariLiburMadrasah($tahun);
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
