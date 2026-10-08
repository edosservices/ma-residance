<?php

declare(strict_types=1);

namespace App\Services\Billing;

use Carbon\CarbonImmutable;

interface ProrataMethod
{
    public function code(): string;

    public function label(): string;

    public function calculate(
        int $monthlyMinor,
        CarbonImmutable $occupiedStart,
        CarbonImmutable $occupiedEnd,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): int;
}
