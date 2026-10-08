<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Support\Money;
use Carbon\CarbonImmutable;

final class DailyProrata implements ProrataMethod
{
    public function code(): string
    {
        return 'daily';
    }

    public function label(): string
    {
        return 'Par jour (jours occupés / jours du mois)';
    }

    public function calculate(
        int $monthlyMinor,
        CarbonImmutable $occupiedStart,
        CarbonImmutable $occupiedEnd,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): int {
        $from = $occupiedStart->startOfDay()->max($periodStart->startOfDay());
        $to = $occupiedEnd->startOfDay()->min($periodEnd->startOfDay());

        if ($to->lessThan($from)) {
            return 0;
        }

        $periodDays = (int) $periodStart->startOfDay()->diffInDays($periodEnd->startOfDay(), true) + 1;
        $occupiedDays = (int) $from->diffInDays($to, true) + 1;

        return Money::prorate($monthlyMinor, $occupiedDays, $periodDays);
    }
}
