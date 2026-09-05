<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppMaintenance;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class AppStatusController extends Controller
{
    public function show(): JsonResponse
    {
        try {
            $item = AppMaintenance::current();
        } catch (QueryException) {
            $item = null;
        }

        $active = (bool) ($item?->is_active);
        $countdown = $active && $item
            ? $item->countdownPayload()
            : ['show_countdown' => false, 'ends_at' => null];

        return response()->json([
            'success' => true,
            'data' => [
                'maintenance' => $active,
                'title' => $active
                    ? ($item?->title ?: 'Sedang dilakukan perbaikan pada server')
                    : null,
                'message' => $active
                    ? ($item?->message ?: 'Mohon maaf, layanan sementara tidak dapat digunakan.')
                    : null,
                'show_countdown' => $countdown['show_countdown'],
                'ends_at' => $countdown['ends_at'],
                'updated_at' => $active
                    ? $item?->updated_at?->copy()->timezone('Asia/Jakarta')->toIso8601String()
                    : null,
            ],
        ]);
    }
}
