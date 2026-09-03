<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Support\SiswaPassword;
use App\Support\SiswaPortalPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class SiswaAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nisn' => ['required', 'digits:10'],
            'password' => ['required', 'string'],
        ]);

        $siswa = Siswa::query()->where('nisn', $data['nisn'])->first();
        $pesanGagal = 'NISN atau kata sandi tidak sesuai.';

        if (! $siswa || ! filled($siswa->getAuthPassword()) || ! Hash::check($data['password'], $siswa->getAuthPassword())) {
            throw ValidationException::withMessages([
                'nisn' => [$pesanGagal],
            ]);
        }

        if (! $siswa->bisaMasuk()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun siswa ini tidak aktif. Hubungi madrasah.',
            ], 403);
        }

        $siswa->tokens()->where('name', 'talim')->delete();
        $token = $siswa->createToken('talim')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'must_change_password' => (bool) $siswa->must_change_password,
            'data' => SiswaPortalPayload::make($siswa),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Siswa $siswa */
        $siswa = $request->user();

        return response()->json([
            'success' => true,
            'must_change_password' => (bool) $siswa->must_change_password,
            'data' => SiswaPortalPayload::make($siswa),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        /** @var Siswa $siswa */
        $siswa = $request->user();
        $awal = SiswaPassword::dariTanggalLahir($siswa->tanggal_lahir);

        if (! Hash::check((string) $request->input('current_password'), $siswa->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => ['Kata sandi saat ini tidak sesuai.'],
            ]);
        }

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8),
                function (string $attribute, mixed $value, \Closure $fail) use ($awal): void {
                    if ($awal !== null && hash_equals($awal, (string) $value)) {
                        $fail('Kata sandi baru tidak boleh sama dengan tanggal lahir.');
                    }
                },
            ],
        ]);

        $siswa->gantiPassword($request->string('password')->toString());

        return response()->json([
            'success' => true,
            'must_change_password' => false,
            'message' => 'Kata sandi berhasil diubah.',
            'data' => SiswaPortalPayload::make($siswa->fresh()),
        ]);
    }
}
