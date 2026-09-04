<?php

namespace App\Http\Controllers;

use App\Models\NotifMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class NotifMediaController extends Controller
{
    public function index(): View
    {
        $items = NotifMedia::query()->orderByDesc('id')->limit(200)->get();

        return view('notifikasi.media', compact('items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:200'],
            'type' => ['required', 'in:image,audio'],
            'file' => [
                'required',
                'file',
                'max:10240',
                $request->input('type') === 'audio'
                    ? 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/ogg,audio/mp4,audio/aac'
                    : 'image',
            ],
        ]);

        $folder = $data['type'] === 'audio' ? 'notifikasi/media/audio' : 'notifikasi/media/image';
        $path = $request->file('file')->store($folder, 'public');

        NotifMedia::query()->create([
            'label' => $data['label'],
            'type' => $data['type'],
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('notifikasi.media.index')->with('success', 'Media disimpan ke pustaka.');
    }

    public function destroy(NotifMedia $media): RedirectResponse
    {
        if ($media->path !== '') {
            Storage::disk('public')->delete($media->path);
        }
        $media->delete();

        return redirect()->route('notifikasi.media.index')->with('success', 'Media dihapus.');
    }
}
