<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\VendorJob;
use App\Services\KartuEPelajarBulkPdfService;
use App\Services\KartuEPelajarPdfService;
use App\Services\Vendor\VendorFotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorJobSiswaController extends Controller
{
    public function uploadFoto(Request $request, VendorJob $vendorJob, Siswa $siswa, VendorFotoService $foto): RedirectResponse
    {
        $this->authorize('uploadFoto', $vendorJob);
        abort_unless($foto->jobMemilikiSiswa($vendorJob, $siswa), 404);

        $request->validate([
            'foto' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:'.VendorFotoService::MAX_KB],
        ]);

        $mengganti = filled($siswa->foto);
        $foto->simpanDariUpload($siswa, $request->file('foto'));

        return redirect()
            ->route('vendor.jobs.show', array_filter([
                'vendorJob' => $vendorJob,
                'q' => $request->input('q'),
                'foto' => $request->input('filter_foto'),
                'rombel_id' => $request->input('rombel_id'),
            ]))
            ->with('status', ($mengganti ? 'Foto diganti: ' : 'Foto disimpan: ').$siswa->nama);
    }

    public function imporZip(Request $request, VendorJob $vendorJob, VendorFotoService $foto): RedirectResponse
    {
        $this->authorize('uploadFoto', $vendorJob);

        $request->validate([
            'zip' => ['required', 'file', 'mimes:zip', 'max:51200'],
        ]);

        $hasil = $foto->imporZip($vendorJob, $request->file('zip'));

        $pesan = "ZIP selesai: {$hasil['imported']} foto diimpor.";
        if ($hasil['missing'] !== []) {
            $pesan .= ' NISN tidak ketemu: '.implode(', ', array_slice($hasil['missing'], 0, 10));
            if (count($hasil['missing']) > 10) {
                $pesan .= '…';
            }
        }
        if ($hasil['invalid'] !== []) {
            $pesan .= ' Gagal validasi: '.count($hasil['invalid']).' berkas.';
        }
        if ($hasil['skipped'] !== []) {
            $pesan .= ' Dilewati: '.count($hasil['skipped']).'.';
        }

        return redirect()
            ->route('vendor.jobs.show', $vendorJob)
            ->with('status', $pesan);
    }

    public function kartuStream(VendorJob $vendorJob, Siswa $siswa, KartuEPelajarPdfService $kartuPdf, VendorFotoService $foto): Response
    {
        $this->authorize('printKartu', $vendorJob);
        abort_unless($foto->jobMemilikiSiswa($vendorJob, $siswa), 404);

        // Sementara: preview/cetak diizinkan tanpa foto (placeholder) untuk verifikasi layout.
        return $kartuPdf->stream($siswa);
    }

    public function kartuBulk(Request $request, VendorJob $vendorJob, KartuEPelajarBulkPdfService $bulk): Response
    {
        $this->authorize('printKartu', $vendorJob);

        $data = $request->validate([
            'siswa_ids' => ['nullable', 'array'],
            'siswa_ids.*' => ['uuid'],
            'semua' => ['nullable', 'boolean'],
            'semua_berfoto' => ['nullable', 'boolean'],
        ]);

        // Sementara: cetak tidak mensyaratkan foto (untuk verifikasi tampilan kartu).
        $query = $vendorJob->siswas();

        if (! ($data['semua'] ?? false) && ! ($data['semua_berfoto'] ?? false)) {
            $ids = $data['siswa_ids'] ?? [];
            abort_if($ids === [], 422, 'Pilih minimal satu siswa.');
            $query->whereIn('siswas.id', $ids);
        }

        $siswas = $query->orderBy('nama')->get();
        abort_if($siswas->isEmpty(), 422, 'Tidak ada siswa untuk dicetak.');

        return $bulk->stream($siswas);
    }
}
