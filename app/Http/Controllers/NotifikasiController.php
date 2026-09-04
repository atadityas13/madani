<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotifikasiFcmJob;
use App\Models\Gtk;
use App\Models\Notifikasi;
use App\Models\Rombel;
use App\Models\Siswa;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NotifikasiController extends Controller
{
    public function index(): View
    {
        try {
            $items = Notifikasi::query()
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->get();
        } catch (QueryException) {
            $items = collect();
            session()->flash('error', 'Tabel notifikasi belum tersedia. Jalankan: php artisan migrate');
        }

        $gtks = Gtk::query()->where('status', 'aktif')->orderBy('nama')->get(['id', 'nama', 'nip']);
        $rombels = Rombel::query()->orderBy('nama')->get(['id', 'nama', 'tingkat']);
        $siswas = Siswa::query()
            ->where('status_keaktifan', '!=', 'nonaktif')
            ->orderBy('nama')
            ->limit(500)
            ->get(['id', 'nama', 'nisn']);

        return view('notifikasi.index', compact('items', 'gtks', 'rombels', 'siswas'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('notifikasis')) {
            return redirect()
                ->route('notifikasi.index')
                ->with('error', 'Tabel notifikasi belum tersedia. Jalankan: php artisan migrate');
        }

        $data = $this->validated($request);
        $notifikasi = Notifikasi::query()->create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
            'published_at' => $data['published_at'] ?? now(),
            'created_by' => $request->user()?->id,
        ]);

        if ($notifikasi->is_active && ($notifikasi->published_at === null || $notifikasi->published_at->lte(now()))) {
            SendNotifikasiFcmJob::dispatch($notifikasi->id);
        }

        return redirect()->route('notifikasi.index')->with('success', 'Notifikasi disimpan dan antrean FCM dipicu.');
    }

    public function update(Request $request, Notifikasi $notifikasi): RedirectResponse
    {
        $data = $this->validated($request);
        $notifikasi->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
            'published_at' => $data['published_at'] ?? $notifikasi->published_at,
        ]);

        return redirect()->route('notifikasi.index')->with('success', 'Notifikasi diperbarui.');
    }

    public function destroy(Notifikasi $notifikasi): RedirectResponse
    {
        $notifikasi->delete();

        return redirect()->route('notifikasi.index')->with('success', 'Notifikasi dihapus.');
    }

    public function resend(Notifikasi $notifikasi): RedirectResponse
    {
        if (! $notifikasi->is_active) {
            return redirect()->route('notifikasi.index')->with('error', 'Notifikasi nonaktif tidak dikirim ulang.');
        }

        SendNotifikasiFcmJob::dispatch($notifikasi->id);

        return redirect()->route('notifikasi.index')->with('success', 'Pengiriman FCM dimasukkan ke antrean.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:200'],
            'isi' => ['required', 'string', 'max:5000'],
            'jenis' => ['required', 'in:pengumuman,pengingat,periode'],
            'audience' => ['required', 'in:semua_guru,semua_siswa,gtk,siswa,rombel'],
            'audience_ids' => ['nullable', 'array'],
            'audience_ids.*' => ['nullable', 'string', 'max:64'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'published_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $needsIds = in_array($data['audience'], [
            Notifikasi::AUDIENCE_GTK,
            Notifikasi::AUDIENCE_SISWA,
            Notifikasi::AUDIENCE_ROMBEL,
        ], true);

        $ids = array_values(array_filter($data['audience_ids'] ?? []));
        if ($needsIds && $ids === []) {
            throw ValidationException::withMessages([
                'audience_ids' => 'Pilih minimal satu penerima.',
            ]);
        }

        $data['audience_ids'] = $needsIds ? $ids : null;
        if ($data['jenis'] !== Notifikasi::JENIS_PERIODE) {
            $data['starts_at'] = $data['starts_at'] ?? null;
            $data['ends_at'] = $data['ends_at'] ?? null;
        }

        return $data;
    }
}
