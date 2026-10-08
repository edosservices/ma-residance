<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Support\DomainException;
use Carbon\CarbonImmutable;

final class ProrataManager
{
    /**
     * @param  array<string, ProrataMethod>  $methods
     */
    public function __construct(private array $methods) {}

    public function calculate(
        string $method,
        int $monthlyMinor,
        CarbonImmutable $occupiedStart,
        CarbonImmutable $occupiedEnd,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): int {
        $calculator = $this->methods[$method] ?? null;

        if (! $calculator instanceof ProrataMethod) {
            throw new DomainException('Méthode de prorata inconnue.');
        }

        return $calculator->calculate($monthlyMinor, $occupiedStart, $occupiedEnd, $periodStart, $periodEnd);
    }

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->methods as $method) {
            $options[$method->code()] = $method->label();
        }

        return $options;
    }
}
