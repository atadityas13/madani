<?php

namespace App\Http\Controllers\Persuratan;

use App\Http\Controllers\Controller;
use App\Models\Gtk;
use App\Services\Persuratan\SptjmTpgPdfService;
use App\Support\Peran;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SptjmTpgController extends Controller
{
    public function generate(Request $request, SptjmTpgPdfService $pdf): Response
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'gtk_ids' => ['required', 'array', 'min:1'],
            'gtk_ids.*' => ['integer', 'distinct', 'exists:gtks,id'],
            'tanggal_surat' => ['required', 'date'],
        ], [
            'gtk_ids.required' => 'Pilih minimal satu guru.',
            'gtk_ids.min' => 'Pilih minimal satu guru.',
            'tanggal_surat.required' => 'Tanggal surat wajib diisi.',
        ]);

        $gtks = Gtk::query()
            ->whereIn('id', $validated['gtk_ids'])
            ->orderBy('nama')
            ->get();

        abort_if($gtks->isEmpty(), 404);

        $tanggal = Carbon::parse($validated['tanggal_surat'])
            ->timezone(config('app.timezone'))
            ->locale('id');

        return $pdf->downloadMany($gtks, $tanggal);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->hasAnyRole([Peran::SUPERADMIN, Peran::ADMIN]), 403);
    }
}
