<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class SiswaPassword
{
    public static function dariTanggalLahir(CarbonInterface|string|null $tanggal): ?string
    {
        if ($tanggal === null || $tanggal === '') {
            return null;
        }

        return Carbon::parse($tanggal)->format('dmY');
    }
}
