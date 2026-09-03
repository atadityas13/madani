<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Services\SiswaBiodataService;
use App\Support\KelengkapanSiswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SiswaPortalController extends Controller
{
    public function __construct(private SiswaBiodataService $biodata) {}

    public function show(Request $request): View|RedirectResponse
    {
        $siswa = $this->siswa();
        $this->biodata->ensureRelasi($siswa);

        if ($request->query('tab') === 'kebutuhan-khusus') {
            return redirect()->route('siswa.portal', ['tab' => 'data-siswa']);
        }

        $siswa->load([
            'orangTuas', 'periodiks.tahunAjaran', 'rombels.tahunAjaran',
            'beasiswas', 'prestasis', 'rekamDidik', 'dokumens', 'ayah', 'ibu',
            'pengajuanPerubahans',
        ]);

        $diminta = $request->query('tab', 'data-siswa');
        $tab = KelengkapanSiswa::tabAman($siswa, is_string($diminta) ? $diminta : 'data-siswa');

        if ($tab !== $diminta) {
            return redirect()->route('siswa.portal', ['tab' => $tab]);
        }

        return view('siswa.show', [
            'siswa' => $siswa,
            'periodik' => $siswa->periodikAktif(),
            'emis' => config('emis'),
            'tab' => $tab,
            'navigasi' => KelengkapanSiswa::navigasi($siswa, $tab),
            'alamatOrtu' => $this->biodata->alamatOrtuUtama($siswa),
            'alamatAsrama' => config('emis.asrama_madrasah'),
            'portal' => true,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $siswa = $this->siswa();
        $bagian = (string) $request->input('bagian', 'data-siswa');

        if ($bagian === 'aktivitas') {
            return redirect()
                ->route('siswa.portal', ['tab' => 'aktivitas'])
                ->with('status', 'Riwayat akademik hanya dapat diubah oleh madrasah.');
        }

        $pesan = $this->biodata->updateBagian($request, $siswa, $bagian, kunciIdentitas: true);
        $tab = in_array($bagian, ['orang-tua', 'alamat', 'beasiswa', 'prestasi', 'rekam-didik'], true)
            ? $bagian
            : 'data-siswa';

        return redirect()
            ->route('siswa.portal', ['tab' => $tab])
            ->with('status', $pesan);
    }

    public function storePengajuan(Request $request): RedirectResponse
    {
        $siswa = $this->siswa();
        $pesan = $this->biodata->ajukanPerubahan($request, $siswa);

        return redirect()
            ->route('siswa.portal', ['tab' => 'data-siswa'])
            ->with('status', $pesan);
    }

    public function destroyRelasi(Request $request): RedirectResponse
    {
        $siswa = $this->siswa();
        $jenis = (string) $request->input('jenis');
        $id = (int) $request->input('id');
        $tab = $this->biodata->hapusRelasi($siswa, $jenis, $id);

        return redirect()
            ->route('siswa.portal', ['tab' => $tab])
            ->with('status', 'Data dihapus.');
    }

    private function siswa(): Siswa
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();

        return $siswa;
    }
}
