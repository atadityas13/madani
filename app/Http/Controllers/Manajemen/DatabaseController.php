<?php

namespace App\Http\Controllers\Manajemen;

use App\Http\Controllers\Controller;
use App\Services\Manajemen\DatabaseResetService;
use App\Services\Manajemen\SiswaExcelImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        return view('manajemen.database', [
            'kartu' => $this->reset->kartu(),
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
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes' => 'File harus berformat .xlsx atau .xls.',
        ]);

        $hasil = $this->siswaExcel->impor($request->file('file'));

        return redirect()
            ->route('manajemen.database')
            ->with('status', $hasil['pesan']);
    }
}
