<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuruApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->gtk_id) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya akun guru/GTK yang dapat mengakses layanan ini.',
            ], 403);
        }

        if (! $user->is_aktif) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Silakan hubungi admin.',
            ], 403);
        }

        return $next($request);
    }
}
