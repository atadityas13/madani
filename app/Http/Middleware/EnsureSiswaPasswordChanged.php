<?php

namespace App\Http\Middleware;

use App\Models\Siswa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSiswaPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $siswa = $request->user('siswa') ?? $request->user();

        if (! $siswa instanceof Siswa || ! $siswa->must_change_password) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'must_change_password' => true,
                'message' => 'Ubah kata sandi sebelum melanjutkan.',
            ], 403);
        }

        return redirect()->route('siswa.password.edit');
    }
}
