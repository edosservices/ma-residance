<?php

declare(strict_types=1);

namespace App\Services\Billing;

use Carbon\CarbonImmutable;

final class BillingCalendar
{
    public function dueOn(CarbonImmutable $month, int $dueDay): CarbonImmutable
    {
        $start = $month->startOfMonth();

        return $start->day(min($dueDay, $start->daysInMonth));
    }

    public function graceEndsOn(CarbonImmutable $dueOn, int $graceUntilDay): CarbonImmutable
    {
        $dueOn = $dueOn->startOfDay();
        $candidate = $dueOn->startOfMonth()->day(min($graceUntilDay, $dueOn->daysInMonth));

        if ($candidate->lessThan($dueOn)) {
            $next = $dueOn->startOfMonth()->addMonth();
            $candidate = $next->day(min($graceUntilDay, $next->daysInMonth));
        }

        return $candidate;
    }

    public function isLate(CarbonImmutable $today, CarbonImmutable $dueOn, int $graceUntilDay): bool
    {
        return $today->startOfDay()->greaterThan($this->graceEndsOn($dueOn, $graceUntilDay));
    }

    public function daysLate(CarbonImmutable $today, CarbonImmutable $dueOn): int
    {
        $today = $today->startOfDay();
        $dueOn = $dueOn->startOfDay();

        if ($today->lessThanOrEqualTo($dueOn)) {
            return 0;
        }

        return (int) $dueOn->diffInDays($today, true);
    }
}
