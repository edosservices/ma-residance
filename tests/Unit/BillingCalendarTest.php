<?php

namespace Tests\Unit;

use App\Services\Billing\BillingCalendar;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class BillingCalendarTest extends TestCase
{
    public function test_grace_ends_on_the_configured_day_of_the_following_month(): void
    {
        $calendar = new BillingCalendar;
        $due = CarbonImmutable::parse('2026-10-30');

        $this->assertTrue($calendar->graceEndsOn($due, 5)->equalTo(CarbonImmutable::parse('2026-11-05')));
        $this->assertFalse($calendar->isLate(CarbonImmutable::parse('2026-11-05'), $due, 5));
        $this->assertTrue($calendar->isLate(CarbonImmutable::parse('2026-11-06'), $due, 5));
        $this->assertSame(7, $calendar->daysLate(CarbonImmutable::parse('2026-11-06'), $due));
        $this->assertSame(0, $calendar->daysLate(CarbonImmutable::parse('2026-10-30'), $due));
    }
}
