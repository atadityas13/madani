<?php

namespace App\Support;

use Illuminate\Support\Str;

class AppMenuHost
{
    public static function isMadaniHost(?string $url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (! is_string($appHost) || $appHost === '') {
            return false;
        }

        $host = Str::lower($host);
        $appHost = Str::lower($appHost);

        return $host === $appHost || Str::endsWith($host, '.'.$appHost);
    }
}
