<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Gtk;
use App\Models\User;
use App\Support\GuruApiPayload;
use App\Support\R2Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GuruProfileController extends Controller
{
    public function updateBiodata(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $gtk = $this->resolveGtk($user);

        $validated = $request->validate([
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'agama' => ['nullable', 'string', 'max:50'],
        ]);

        $gtk->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data pribadi berhasil diperbarui.',
            'user' => GuruApiPayload::user($user->fresh()->load('gtk')),
        ]);
    }

    public function updateKontak(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $gtk = $this->resolveGtk($user);

        $validated = $request->validate([
            'nomor_hp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ]);

        $gtk->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data kontak berhasil diperbarui.',
            'user' => GuruApiPayload::user($user->fresh()->load('gtk')),
        ]);
    }

    public function updatePhoto(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->resolveGtk($user);

        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ], [
            'foto.required' => 'File foto harus dipilih.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        if (filled($user->foto) && ! str_starts_with((string) $user->foto, 'http')) {
            Storage::disk('r2')->delete($user->foto);
            Storage::disk('public')->delete($user->foto);
        }

        $path = $request->file('foto')->store('user_photos', 'r2');
        $user->foto = $path;
        $user->save();

        $user->gtk?->update(['foto_url' => R2Url::public($path)]);

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui.',
            'user' => GuruApiPayload::user($user->fresh()->load('gtk')),
        ]);
    }

    private function resolveGtk(User $user): Gtk
    {
        $gtk = $user->gtk;
        if (! $gtk) {
            abort(403, 'Akun tidak terhubung ke data GTK.');
        }

        return $gtk;
    }
}
