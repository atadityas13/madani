<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Support\SiswaPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SiswaAuthController extends Controller
{
    public function create(): View
    {
        return view('portal.masuk');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nisn' => ['required', 'digits:10'],
            'password' => ['required', 'string'],
        ]);

        $siswa = Siswa::query()->where('nisn', $data['nisn'])->first();
        $pesanGagal = 'NISN atau kata sandi tidak sesuai.';

        if (! $siswa || ! filled($siswa->getAuthPassword()) || ! Hash::check($data['password'], $siswa->getAuthPassword())) {
            return back()->withErrors(['nisn' => $pesanGagal])->onlyInput('nisn');
        }

        if (! $siswa->bisaMasuk()) {
            return back()->withErrors([
                'nisn' => 'Akun siswa ini tidak aktif. Hubungi madrasah.',
            ])->onlyInput('nisn');
        }

        Auth::guard('siswa')->login($siswa, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($siswa->must_change_password) {
            return redirect()->route('siswa.password.edit');
        }

        return redirect()->intended(route('siswa.portal'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('siswa')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('siswa.masuk');
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
                        $fail('Kata sandi baru tidak boleh sama dengan tanggal lahir.');
                    }
                },
            ],
        ]);

        $siswa->gantiPassword($request->string('password')->toString());

        return redirect()
            ->route('siswa.portal')
            ->with('status', 'Kata sandi berhasil diubah.');
    }
}
