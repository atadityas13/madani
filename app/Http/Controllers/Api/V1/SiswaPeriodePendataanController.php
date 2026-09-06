<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PeriodePendataan;
use Illuminate\Http\JsonResponse;

class SiswaPeriodePendataanController extends Controller
{
    public function show(): JsonResponse
    {
        $periode = PeriodePendataan::current();

        return response()->json([
            'success' => true,
            'data' => $periode?->siswaPayload() ?? ['active' => false],
        ]);
    }
}
