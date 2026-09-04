<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotifikasiFcmJob;
use App\Models\Gtk;
use App\Models\Notifikasi;
use App\Models\NotifMedia;
use App\Models\Rombel;
use App\Models\Siswa;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
        $mediaImages = NotifMedia::query()->where('type', NotifMedia::TYPE_IMAGE)->orderByDesc('id')->limit(100)->get();
        $mediaAudios = NotifMedia::query()->where('type', NotifMedia::TYPE_AUDIO)->orderByDesc('id')->limit(100)->get();
        $scheduled = $items->filter(fn (Notifikasi $n) => $n->scheduled_at !== null && $n->sent_at === null);

        return view('notifikasi.index', compact(
            'items',
            'gtks',
            'rombels',
            'siswas',
            'mediaImages',
            'mediaAudios',
            'scheduled',
        ));
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

        if ($this->shouldDispatchFcm($notifikasi)) {
            SendNotifikasiFcmJob::dispatch($notifikasi->id);
        }

        $msg = $notifikasi->scheduled_at && $notifikasi->scheduled_at->isFuture()
            ? 'Notifikasi dijadwalkan.'
            : 'Notifikasi disimpan dan antrean FCM dipicu.';

        return redirect()->route('notifikasi.index')->with('success', $msg);
    }

    public function update(Request $request, Notifikasi $notifikasi): RedirectResponse
    {
        $data = $this->validated($request, $notifikasi);
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
    private function validated(Request $request, ?Notifikasi $existing = null): array
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:200'],
            'isi' => ['required', 'string', 'max:5000'],
            'jenis' => ['required', 'in:notifikasi,pengumuman,pengingat'],
            'audience' => ['required', 'in:semua_guru,semua_siswa,gtk,siswa,rombel'],
            'audience_ids' => ['nullable', 'array'],
            'audience_ids.*' => ['nullable', 'string', 'max:64'],
            'gambar' => ['nullable', 'image', 'max:4096'],
            'gambar_media_id' => ['nullable', 'integer', 'exists:notif_media,id'],
            'audio' => ['nullable', 'file', 'max:10240', 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/ogg,audio/mp4,audio/aac'],
            'audio_media_id' => ['nullable', 'integer', 'exists:notif_media,id'],
            'link' => ['nullable', 'url', 'max:500'],
            'sound_key' => ['nullable', Rule::in(array_keys(Notifikasi::soundOptions()))],
            'priority' => ['nullable', Rule::in(array_keys(Notifikasi::priorityOptions()))],
            'use_periode' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
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
        $data['use_periode'] = $data['jenis'] === Notifikasi::JENIS_PENGINGAT && $request->boolean('use_periode');
        $data['link'] = $data['link'] ?? null;
        $data['sound_key'] = $data['sound_key'] ?? Notifikasi::SOUND_DEFAULT;
        $data['priority'] = $data['priority'] ?? Notifikasi::PRIORITY_NORMAL;
        $data['scheduled_at'] = $data['scheduled_at'] ?? null;

        if ($data['use_periode']) {
            if (empty($data['starts_at']) || empty($data['ends_at'])) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Periode pengingat membutuhkan tanggal mulai dan selesai.',
                ]);
            }
        } else {
            $data['starts_at'] = null;
            $data['ends_at'] = null;
        }

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('notifikasi', 'r2');
            $data['gambar_url'] = Storage::disk('r2')->url($path);
        } elseif (! empty($data['gambar_media_id'])) {
            $media = NotifMedia::query()->find($data['gambar_media_id']);
            if ($media?->type === NotifMedia::TYPE_IMAGE) {
                $data['gambar_url'] = $media->url;
            } else {
                $data['gambar_url'] = $existing?->gambar_url;
            }
        } else {
            $data['gambar_url'] = $existing?->gambar_url;
        }

        if ($request->hasFile('audio')) {
            $path = $request->file('audio')->store('notifikasi/audio', 'r2');
            $data['audio_url'] = Storage::disk('r2')->url($path);
        } elseif (! empty($data['audio_media_id'])) {
            $media = NotifMedia::query()->find($data['audio_media_id']);
            if ($media?->type === NotifMedia::TYPE_AUDIO) {
                $data['audio_url'] = $media->url;
            } else {
                $data['audio_url'] = $existing?->audio_url;
            }
        } else {
            $data['audio_url'] = $existing?->audio_url;
        }

        unset($data['gambar'], $data['audio'], $data['gambar_media_id'], $data['audio_media_id']);

        return $data;
    }

    private function shouldDispatchFcm(Notifikasi $notifikasi): bool
    {
        if (! $notifikasi->is_active) {
            return false;
        }

        if ($notifikasi->published_at !== null && $notifikasi->published_at->isFuture()) {
            return false;
        }

        if ($notifikasi->scheduled_at !== null && $notifikasi->scheduled_at->isFuture()) {
            return false;
        }

        return true;
    }
}
