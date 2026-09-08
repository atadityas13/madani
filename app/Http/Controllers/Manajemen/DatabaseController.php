<?php

namespace App\Http\Controllers\Manajemen;

use App\Http\Controllers\Controller;
use App\Services\Manajemen\DatabaseResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class DatabaseController extends Controller
{
    public function __construct(private DatabaseResetService $reset) {}

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
}
