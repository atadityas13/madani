<?php

namespace App\Http\Controllers;

use App\Models\Gtk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GtkController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $gtks = Gtk::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', "%{$q}%")
                        ->orWhere('nip', 'like', "%{$q}%")
                        ->orWhere('nuptk', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('gtk.index', compact('gtks', 'q'));
    }

    public function create(): View
    {
        return view('gtk.form', [
            'gtk' => new Gtk(['status' => 'aktif']),
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

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Gtk $gtk = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:30'],
            'nuptk' => ['nullable', 'string', 'max:20', Rule::unique('gtks', 'nuptk')->ignore($gtk?->id)],
            'jenis_kelamin' => ['nullable', Rule::in(['L', 'P'])],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        $data['nip'] = $data['nip'] ?? null;
        $data['nuptk'] = $data['nuptk'] ?? null;

        return $data;
    }
}
