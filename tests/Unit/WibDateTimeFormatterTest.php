<?php

namespace Tests\Unit;

use App\Support\WibDateTimeFormatter;
use Carbon\Carbon;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class WibDateTimeFormatterTest extends TestCase
{
    public function test_it_converts_utc_to_wib_with_a_seven_hour_offset(): void
    {
        $this->assertSame(
            '10-11-2026 11:06',
            WibDateTimeFormatter::format('2026-11-10 04:06:00')
        );
    }

    public function test_it_formats_date_and_time_as_day_month_year_and_hour_minute(): void
    {
        $this->assertSame(
            '10-11-2026 11:06',
            WibDateTimeFormatter::format('2026-11-10T04:06:59+00:00')
        );
    }

    public function test_it_returns_null_for_null_or_empty_values(): void
    {
        $this->assertNull(WibDateTimeFormatter::format(null));
        $this->assertNull(WibDateTimeFormatter::format(''));
        $this->assertNull(WibDateTimeFormatter::format('   '));
    }

    public function test_it_does_not_change_the_source_date_object(): void
    {
        $source = Carbon::parse('2026-11-10 04:06:00', 'UTC');
        $originalValue = $source->format('Y-m-d H:i:s P e');

        $this->assertSame(
            '10-11-2026 11:06',
            WibDateTimeFormatter::format($source)
        );
        $this->assertSame($originalValue, $source->format('Y-m-d H:i:s P e'));
        $this->assertSame('UTC', $source->getTimezone()->getName());
    }

    public function test_it_respects_the_timezone_of_a_zoned_date_without_double_conversion(): void
    {
        $source = new DateTimeImmutable(
            '2026-11-10 11:06:00',
            new \DateTimeZone('Asia/Jakarta')
        );

        $this->assertSame(
            '10-11-2026 11:06',
            WibDateTimeFormatter::format($source)
        );
        $this->assertSame(
            '10-11-2026 11:06',
            WibDateTimeFormatter::format('2026-11-10T11:06:00+07:00')
        );
    }
}
