<?php

namespace App\Support;

final class DisplayNumber
{
    public static function format(?float $value, int $decimals): string
    {
        $formatted = number_format($value ?? 0, $decimals, ',', '.');

        if ($decimals === 0) {
            return $formatted;
        }

        return rtrim(rtrim($formatted, '0'), ',');
    }
}
