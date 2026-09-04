<?php

namespace App\Http\Controllers;

use App\Models\Gtk;
use App\Models\User;
use App\Support\GtkAkun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GtkController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $jenis = trim((string) $request->query('jenis', ''));

        $gtks = Gtk::query()
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
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('gtk.index', [
            'gtks' => $gtks,
            'q' => $q,
            'jenis' => $jenis,
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

    public function destroy(Gtk $gtk): RedirectResponse
    {
        if ($gtk->rombels()->exists()) {
            return back()->with('status', 'GTK tidak dapat dihapus karena masih menjadi wali kelas.');
        }

        $gtk->delete();

        return redirect()->route('gtk.index')->with('status', 'GTK dihapus.');
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
            'jenis' => ['required', Rule::in(array_keys(Gtk::jenisOptions()))],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        foreach (['nip', 'nuptk', 'gelar_depan', 'gelar_belakang', 'tempat_lahir', 'agama', 'nomor_hp', 'email', 'alamat', 'jabatan', 'golongan', 'status_pegawai', 'kode_internal', 'duk'] as $field) {
            $data[$field] = $data[$field] ?? null;
            if (is_string($data[$field]) && trim($data[$field]) === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
