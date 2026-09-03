<?php

namespace App\Http\Controllers;

use App\Models\Madrasah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class KelembagaanController extends Controller
{
    public function identitas(): View
    {
        return view('kelembagaan.identitas', [
            'madrasah' => Madrasah::saatIni(),
            'bisaUbah' => auth()->user()?->bisaUbahIdentitas() ?? false,
        ]);
    }

    public function updateIdentitas(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->bisaUbahIdentitas(), 403);

        $madrasah = Madrasah::saatIni();
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'npsn' => ['nullable', 'string', 'max:20'],
            'nsm' => ['nullable', 'string', 'max:20'],
            'jenjang' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:20'],
            'akreditasi' => ['nullable', 'string', 'max:10'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'desa' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kota' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        unset($data['logo']);

        if ($request->hasFile('logo')) {
            if ($madrasah->logo_path) {
                Storage::disk('r2')->delete($madrasah->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('madrasah', 'r2');
        }

        $madrasah->update($data);

        return redirect()
            ->route('kelembagaan.identitas')
            ->with('status', 'Identitas madrasah disimpan.');
    }
}
