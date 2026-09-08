<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Support\SiswaPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SiswaAuthController extends Controller
{
    /**
     * Portal web siswa dinonaktifkan sementara — masuk lewat Ta'lim.
     */
    public function create(): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->with('error', 'Portal web siswa dinonaktifkan. Silakan masuk lewat aplikasi Ta\'lim.');
    }

    public function store(): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->with('error', 'Portal web siswa dinonaktifkan. Silakan masuk lewat aplikasi Ta\'lim.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('siswa')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function editPassword(): View
    {
        return view('portal.password', [
            'siswa' => Auth::guard('siswa')->user(),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();
        $awal = SiswaPassword::dariTanggalLahir($siswa->tanggal_lahir);

        $request->validate([
            'current_password' => ['required', 'current_password:siswa'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8),
                function (string $attribute, mixed $value, \Closure $fail) use ($awal): void {
                    if ($awal !== null && hash_equals($awal, (string) $value)) {
                        $fail('Kata sandi baru tidak boleh sama dengan password awal (tanggal lahir).');
                    }
                },
            ],
        ]);

        $siswa->forceFill([
            'password' => $request->string('password')->toString(),
            'must_change_password' => false,
        ])->save();

        return redirect()->route('siswa.portal');
    }
}
