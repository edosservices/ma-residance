<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_daily_prorata_rounds_half_up_in_minor_units(): void
    {
        $this->assertSame(3839, Money::prorate(7000, 17, 31));
        $this->assertSame(7000, Money::prorate(7000, 31, 31));
        $this->assertSame(0, Money::prorate(7000, 0, 31));
    }

    public function test_exchange_conversion_keeps_integer_minor_units(): void
    {
        $this->assertSame(20300000, Money::convertMinor(7000, '2900'));
        $this->assertSame(7000, Money::convertMinorInverse(20300000, '2900'));
    }

    public function test_amounts_are_parsed_without_floats(): void
    {
        $this->assertSame(7000, Money::toMinor('70'));
        $this->assertSame(7000, Money::toMinor('70,00'));
        $this->assertSame(10000000, Money::toMinor('100 000'));
    }
}
