<?php

namespace App\Http\Controllers\Persuratan;

use App\Http\Controllers\Controller;
use App\Models\Gtk;
use App\Support\Peran;
use Illuminate\View\View;

class PersuratanController extends Controller
{
    public function index(): View
    {
        $this->authorizeAdmin();

        $gtks = Gtk::query()
            ->where('status', 'aktif')
            ->orderBy('nama')
            ->get(['id', 'nama', 'gelar_depan', 'gelar_belakang', 'nuptk', 'nrg']);

        return view('persuratan.index', [
            'gtks' => $gtks,
            'suratList' => [
                [
                    'kode' => 'sptjm-tpg',
                    'judul' => 'SPTJM TPG',
                    'deskripsi' => 'Surat Pernyataan Tanggung Jawab Mutlak — Tunjangan Profesi Guru',
                    'preview' => asset('images/persuratan/sptjm-tpg-preview.png'),
                    'generate_route' => route('persuratan.sptjm-tpg.generate'),
                ],
            ],
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->hasAnyRole([Peran::SUPERADMIN, Peran::ADMIN]), 403);
    }
}
