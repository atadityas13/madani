<?php

namespace App\Http\Controllers;

use App\Models\Gtk;
use App\Models\User;
use App\Services\Simpatisans\GtkSyncService;
use App\Support\GtkAkun;
use App\Support\UkuranHalaman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class GtkController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $jenis = trim((string) $request->query('jenis', ''));

        $query = Gtk::query()
            ->with('akun')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', "%{$q}%")
                        ->orWhere('nip', 'like', "%{$q}%")
                        ->orWhere('nuptk', 'like', "%{$q}%");
                });
            })
            ->when($jenis !== '' && array_key_exists($jenis, Gtk::jenisOptions()), function ($query) use ($jenis) {
                $query->where('jenis', $jenis);
            })
            ->orderBy('nama');

        $hasil = UkuranHalaman::paginateQuery($query, $request, UkuranHalaman::STANDAR, 15);

        return view('gtk.index', [
            'gtks' => $hasil['baris'],
            'q' => $q,
            'jenis' => $jenis,
            'perPage' => $hasil['label'],
        ]);
    }

    public function create(): View
    {
        return view('gtk.form', [
            'gtk' => new Gtk(['status' => 'aktif', 'jenis' => Gtk::JENIS_GURU]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gtk::query()->create($this->validated($request));

        return redirect()->route('gtk.index')->with('status', 'GTK ditambahkan.');
    }

    public function edit(Gtk $gtk): View
    {
        return view('gtk.form', compact('gtk'));
    }

    public function update(Request $request, Gtk $gtk): RedirectResponse
    {
        $gtk->update($this->validated($request, $gtk));

        return redirect()->route('gtk.index')->with('status', 'GTK diperbarui.');
    }

    public function bandingkanSimpatisans(GtkSyncService $sinkron): View|RedirectResponse
    {
        try {
            $hasil = $sinkron->fetchAndRemember();
        } catch (RuntimeException $e) {
            return redirect()
                ->route('gtk.index')
                ->with('error', $e->getMessage());
        }

        return view('gtk.sync', $hasil);
    }

    public function terapkanSimpatisans(GtkSyncService $sinkron): RedirectResponse
    {
        try {
            $hasil = $sinkron->terapkanDariSesi();
        } catch (RuntimeException $e) {
            return redirect()
                ->route('gtk.sync-simpatisans')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('gtk.sync-simpatisans')
            ->with('status', sprintf(
                'Sinkron guru selesai: %d ditambahkan, %d diperbarui. DUK mengikuti Simpatisans. Isian lain hanya diisi bila masih kosong.',
                $hasil['dibuat'],
                $hasil['diperbarui'],
            ));
    }

    public function destroy(Request $request, Gtk $gtk): RedirectResponse
    {
        if ($gtk->rombels()->exists()) {
            return back()->with('status', 'GTK tidak dapat dihapus karena masih menjadi wali kelas.');
        }

        $gtk->delete();

        $route = $request->input('kembali') === 'sync' ? 'gtk.sync-simpatisans' : 'gtk.index';

        return redirect()->route($route)->with('status', 'GTK dihapus.');
    }

    public function buatAkun(Gtk $gtk, GtkAkun $akun): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = $akun->buat($gtk);

        return back()->with(
            'status',
            "Akun Ta'lim dibuat. Username: {$user->username}. Password awal = NIP (wajib diganti saat login pertama).",
        );
    }

    public function resetPassword(Gtk $gtk, GtkAkun $akun): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = $gtk->akun;
        if (! $user) {
            return back()->with('status', 'GTK ini belum punya akun. Buat akun terlebih dahulu.');
        }

        $password = $akun->resetPassword($user);

        return back()->with(
            'status',
            "Password akun {$user->username} direset ke: {$password}. Pengguna wajib mengganti saat login berikutnya.",
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Gtk $gtk = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'gelar_depan' => ['nullable', 'string', 'max:50'],
            'gelar_belakang' => ['nullable', 'string', 'max:50'],
            'nip' => ['nullable', 'string', 'max:30'],
            'nuptk' => ['nullable', 'string', 'max:20', Rule::unique('gtks', 'nuptk')->ignore($gtk?->id)],
            'nrg' => ['nullable', 'string', 'max:40'],
            'jenis_kelamin' => ['nullable', Rule::in(['L', 'P'])],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'agama' => ['nullable', 'string', 'max:30'],
            'nomor_hp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'alamat' => ['nullable', 'string'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'golongan' => ['nullable', 'string', 'max:50'],
            'status_pegawai' => ['nullable', 'string', 'max:30'],
            'kode_internal' => ['nullable', 'string', 'max:40'],
            'duk' => ['nullable', 'string', 'max:40'],
            'mapel_ijazah' => ['nullable', 'string', 'max:120'],
            'mapel_sertifikasi' => ['nullable', 'string', 'max:120'],
            'status_sertifikasi' => ['sometimes', 'boolean'],
            'is_bk' => ['sometimes', 'boolean'],
            'jenis' => ['required', Rule::in(array_keys(Gtk::jenisOptions()))],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        $data['status_sertifikasi'] = $request->boolean('status_sertifikasi');
        $data['is_bk'] = $request->boolean('is_bk');

        foreach (['nip', 'nuptk', 'nrg', 'gelar_depan', 'gelar_belakang', 'tempat_lahir', 'agama', 'nomor_hp', 'email', 'alamat', 'jabatan', 'golongan', 'status_pegawai', 'kode_internal', 'duk', 'mapel_ijazah', 'mapel_sertifikasi'] as $field) {
            $data[$field] = $data[$field] ?? null;
            if (is_string($data[$field]) && trim($data[$field]) === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
