<?php

namespace App\Support;

class TextUnescape
{
    public static function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = stripcslashes($value);
        $value = str_replace(["\\'", '\\"'], ["'", '"'], $value);
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
