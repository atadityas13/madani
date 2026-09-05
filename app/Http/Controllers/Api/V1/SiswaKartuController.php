<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Services\KartuEPelajarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiswaKartuController extends Controller
{
    public function __construct(private KartuEPelajarService $kartu) {}

    public function show(Request $request): JsonResponse
    {
        /** @var Siswa $siswa */
        $siswa = $request->user();

        return response()->json([
            'success' => true,
            'data' => $this->kartu->payload($siswa),
        ]);
    }
}
