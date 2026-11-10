<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class WibDateTimeFormatter
{
    public static function format(DateTimeInterface|string|null $value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        $dateTime = $value instanceof DateTimeInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse($value, 'UTC');

        return $dateTime
            ->setTimezone('Asia/Jakarta')
            ->format('d-m-Y H:i');
    }
}
