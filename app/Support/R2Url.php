<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

class R2Url
{
    /**
     * URL publik permanen (R2_URL). Cocok untuk aset yang di-cache di app
     * (foto profil, media notifikasi) agar tidak kedaluwarsa seperti temporary URL.
     */
    public static function public(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $path = (string) $path;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        try {
            return Storage::disk('r2')->url($path);
        } catch (Throwable) {
            return null;
        }
    }

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
            return self::public($path);
        }
    }
}
