<?php

namespace Tests\Unit;

use App\Support\InventoryNumberFormatter;
use PHPUnit\Framework\TestCase;

class InventoryNumberFormatterTest extends TestCase
{
    public function test_it_formats_whole_numbers_without_trailing_zeroes(): void
    {
        $this->assertSame('10', InventoryNumberFormatter::format('10.000'));
        $this->assertSame('1.500', InventoryNumberFormatter::format('1500.000'));
    }

    public function test_it_formats_decimal_values_and_removes_unneeded_zeroes(): void
    {
        $this->assertSame('10,5', InventoryNumberFormatter::format('10.500'));
        $this->assertSame('10,25', InventoryNumberFormatter::format('10.250'));
        $this->assertSame('1.234,567', InventoryNumberFormatter::format('1234.567'));
    }

    public function test_it_formats_zero_as_zero(): void
    {
        $this->assertSame('0', InventoryNumberFormatter::format('0.000'));
    }

    public function test_it_displays_at_most_three_decimal_places(): void
    {
        $this->assertSame('1,235', InventoryNumberFormatter::format('1.23456'));
    }
}
