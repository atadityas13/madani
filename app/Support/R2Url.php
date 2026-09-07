<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class R2Url
{
    /**
     * URL publik permanen (R2_URL). Hanya aman jika bucket/object memang public.
     * Untuk aset privat (foto, dokumen, media notifikasi) pakai readable()/temporary().
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

        $path = (string) $path;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        try {
            return Storage::disk('r2')->temporaryUrl($path, now()->addMinutes($minutes));
        } catch (Throwable) {
            return self::public($path);
        }
    }

    /**
     * Resolve path atau URL R2 tersimpan menjadi URL yang bisa dibaca klien
     * (signed temporary bila bucket privat). URL eksternal dibiarkan apa adanya.
     */
    public static function readable(?string $stored, int $minutes = 60 * 24): ?string
    {
        if (! filled($stored)) {
            return null;
        }

        $stored = (string) $stored;

        if (str_starts_with($stored, 'http://') || str_starts_with($stored, 'https://')) {
            $path = self::objectPathFromPublicUrl($stored);
            if ($path === null) {
                return $stored;
            }

            return self::temporary($path, $minutes) ?? $stored;
        }

        return self::temporary($stored, $minutes);
    }

    private static function objectPathFromPublicUrl(string $url): ?string
    {
        $base = rtrim((string) config('filesystems.disks.r2.url'), '/');
        if ($base === '' || ! Str::startsWith($url, $base.'/')) {
            return null;
        }

        $path = ltrim(substr($url, strlen($base)), '/');

        return $path !== '' ? $path : null;
    }
}
