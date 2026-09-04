<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

class R2Url
{
    /**
     * URL baca berkas R2. Pakai signed temporary URL agar preview
     * tetap jalan meski bucket privat (tanpa public access).
     */
    public static function temporary(?string $path, int $minutes = 60): ?string
    {
        if (! filled($path)) {
            return null;
        }

        try {
            return Storage::disk('r2')->temporaryUrl($path, now()->addMinutes($minutes));
        } catch (Throwable) {
            return Storage::disk('r2')->url($path);
        }
    }
}
