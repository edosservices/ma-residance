<?php

namespace Tests\Unit;

use App\Services\Billing\DailyProrata;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class DailyProrataTest extends TestCase
{
    public function test_full_month_mid_month_month_end_february_and_leap_year(): void
    {
        $prorata = new DailyProrata;
        $rent = 7000;

        $october = CarbonImmutable::parse('2026-10-01');
        $this->assertSame(7000, $prorata->calculate($rent, $october, $october->endOfMonth(), $october, $october->endOfMonth()));
        $this->assertSame(3839, $prorata->calculate($rent, CarbonImmutable::parse('2026-10-15'), $october->endOfMonth(), $october, $october->endOfMonth()));
        $this->assertSame(226, $prorata->calculate($rent, CarbonImmutable::parse('2026-10-31'), $october->endOfMonth(), $october, $october->endOfMonth()));

        $february = CarbonImmutable::parse('2026-02-01');
        $this->assertSame(3500, $prorata->calculate($rent, CarbonImmutable::parse('2026-02-15'), $february->endOfMonth(), $february, $february->endOfMonth()));

        $leap = CarbonImmutable::parse('2024-02-01');
        $this->assertSame(3621, $prorata->calculate($rent, CarbonImmutable::parse('2024-02-15'), $leap->endOfMonth(), $leap, $leap->endOfMonth()));
    }
}
