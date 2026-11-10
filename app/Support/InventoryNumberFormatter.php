<?php

namespace App\Support;

class InventoryNumberFormatter
{
    public static function format(int|float|string $value): string
    {
        $formatted = number_format((float) $value, 3, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',');
    }
}
