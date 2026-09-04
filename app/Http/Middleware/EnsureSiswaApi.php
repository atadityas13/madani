<?php

namespace App\Http\Middleware;

use App\Models\Siswa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSiswaApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof Siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya akun siswa yang dapat mengakses layanan ini.',
            ], 403);
        }

        if (! $user->bisaMasuk()) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'success' => false,
                'message' => 'Akun siswa ini tidak aktif. Hubungi madrasah.',
            ], 403);
        }

        return $next($request);
    }
}
