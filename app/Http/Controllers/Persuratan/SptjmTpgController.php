<?php

namespace App\Http\Controllers\Persuratan;

use App\Http\Controllers\Controller;
use App\Models\Gtk;
use App\Services\Persuratan\SptjmTpgPdfService;
use App\Support\Peran;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SptjmTpgController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $q = trim((string) $request->query('q', ''));

        $gtks = Gtk::query()
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('nama', 'like', "%{$q}%")
                        ->orWhere('nip', 'like', "%{$q}%")
                        ->orWhere('nuptk', 'like', "%{$q}%")
                        ->orWhere('nrg', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama')
            ->paginate(25)
            ->withQueryString();

        return view('persuratan.sptjm-tpg.index', [
            'gtks' => $gtks,
            'q' => $q,
        ]);
    }

    public function pdf(Gtk $gtk, SptjmTpgPdfService $pdf): Response
    {
        $this->authorizeAdmin();

        return $pdf->download($gtk);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->hasAnyRole([Peran::SUPERADMIN, Peran::ADMIN]), 403);
    }
}
