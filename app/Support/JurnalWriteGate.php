<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class JurnalWriteGate
{
    public static function denyIfDisabled(): ?JsonResponse
    {
        if (config('jurnal.writes_enabled', true)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Penulisan jurnal sementara dinonaktifkan (migrasi/verifikasi data).',
        ], 503);
    }
}
