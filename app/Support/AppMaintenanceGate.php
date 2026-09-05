<?php

namespace App\Support;

use App\Models\AppMaintenance;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class AppMaintenanceGate
{
    public static function denyIfActive(): ?JsonResponse
    {
        try {
            $item = AppMaintenance::current();
        } catch (QueryException) {
            return null;
        }

        if (! $item?->is_active) {
            return null;
        }

        return response()->json([
            'success' => false,
            'maintenance' => true,
            'message' => $item->message
                ?: 'Sedang dilakukan perbaikan pada server. Silakan coba lagi nanti.',
            'title' => $item->title
                ?: 'Sedang dilakukan perbaikan pada server',
        ], 503);
    }
}
