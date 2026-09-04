<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Gtk;
use App\Models\User;
use App\Support\GuruApiPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class GuruAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'NIP/NIK harus diisi.',
            'password.required' => 'Password harus diisi.',
        ]);

        $username = trim($credentials['username']);
        $user = User::query()
            ->with('gtk')
            ->where('username', $username)
            ->first();

        if (! $user || ! $user->gtk_id) {
            $gtkExists = Gtk::query()->where('nip', $username)->exists();
            $message = $gtkExists
                ? 'Akun Anda belum diaktifkan, silakan hubungi Admin.'
                : 'NIP/NIK belum terdaftar di sistem.';

            throw ValidationException::withMessages(['username' => [$message]]);
        }

        if (! $user->is_aktif) {
            throw ValidationException::withMessages([
                'username' => ['Akun Anda dinonaktifkan. Silakan hubungi admin.'],
            ]);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Password salah. Coba lagi.'],
            ]);
        }

        $user->tokens()->where('name', 'talim-guru')->delete();
        $token = $user->createToken('talim-guru')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => GuruApiPayload::user($user),
            'requires_password_change' => (bool) $user->must_change_password,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => GuruApiPayload::user($user->load('gtk')),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'current_password.required' => 'Password saat ini harus diisi.',
            'password.required' => 'Password baru harus diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini tidak cocok.'],
            ]);
        }

        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'message' => 'Logout berhasil.']);
    }
}
