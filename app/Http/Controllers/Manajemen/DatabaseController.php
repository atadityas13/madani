<?php

namespace App\Http\Controllers\Manajemen;

use App\Http\Controllers\Controller;
use App\Services\Manajemen\DatabaseResetService;
use App\Services\Manajemen\SiswaExcelImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseController extends Controller
{
    public function __construct(
        private DatabaseResetService $reset,
        private SiswaExcelImportService $siswaExcel,
    ) {}

    public function index(): View
    {
        $duplikat = null;
        $token = session('impor_siswa_duplikat_token');

        if (is_string($token) && $token !== '') {
            $payload = Cache::get('impor_siswa_duplikat_'.$token);
            if (is_array($payload) && isset($payload['conflicts'], $payload['pesan_duplikat'])) {
                $duplikat = [
                    'token' => $token,
                    'pesan' => $payload['pesan_duplikat'],
                    'conflicts' => $payload['conflicts'],
                    'jumlah' => count($payload['conflicts']),
                ];
            }
        }

        return view('manajemen.database', [
            'kartu' => $this->reset->kartu(),
            'imporDuplikat' => $duplikat,
            'imporSuksesJumlah' => session('impor_siswa_sukses_jumlah'),
        ]);
    }

    public function kosongkan(string $modul): RedirectResponse
    {
        try {
            $hasil = $this->reset->kosongkan($modul);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        return redirect()
            ->route('manajemen.database')
            ->with('status', $hasil['pesan']);
    }

    public function templateSiswa(): StreamedResponse
    {
        return $this->siswaExcel->unduhTemplate();
    }

    public function imporSiswa(Request $request): RedirectResponse
    {
        if (function_exists('set_time_limit')) {
            set_time_limit(300);
        }

        if ($request->boolean('skip_duplikat')) {
            $request->validate([
                'token' => ['required', 'string'],
            ]);

            $hasil = $this->siswaExcel->imporLewatiDuplikat($request->string('token')->toString());

            return redirect()
                ->route('manajemen.database')
                ->with('impor_siswa_sukses_jumlah', $hasil['imported']);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes' => 'File harus berformat .xlsx atau .xls.',
        ]);

        $hasil = $this->siswaExcel->impor($request->file('file'));

        if ($hasil['status'] === 'duplikat') {
            return redirect()
                ->route('manajemen.database')
                ->with('impor_siswa_duplikat_token', $hasil['token']);
        }

        return redirect()
            ->route('manajemen.database')
            ->with('impor_siswa_sukses_jumlah', $hasil['imported']);
    }

    public function eksporDuplikatSiswa(Request $request): StreamedResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        return $this->siswaExcel->unduhDuplikat($request->string('token')->toString());
    }
}
