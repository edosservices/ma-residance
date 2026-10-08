<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use App\Models\ExchangeRate;
use App\Models\Organization;
use App\Models\User;
use App\Support\DomainException;
use App\Support\Money;
use Carbon\CarbonImmutable;

class ExchangeRateService
{
    public function __construct(private AuditLogger $audit) {}

    public function record(Organization $organization, User $actor, string $base, string $quote, string $rate): ExchangeRate
    {
        $base = strtoupper($base);
        $quote = strtoupper($quote);

        if ($base === $quote) {
            throw new DomainException('Les deux devises doivent être différentes.');
        }

        $row = ExchangeRate::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'base_currency' => $base,
            'quote_currency' => $quote,
            'rate' => Money::normalizeRate($rate),
            'effective_at' => CarbonImmutable::now($organization->timezone),
            'created_by' => $actor->id,
        ]);

        $this->audit->log(
            $organization->id,
            $actor,
            'exchange_rate.created',
            $row,
            "A enregistré le taux 1 {$base} = ".rtrim(rtrim($row->rate, '0'), '.')." {$quote}.",
        );

        return $row;
    }

    /**
     * @return array{exchange_rate_id: int, fx_base_currency: string, fx_quote_currency: string, fx_rate: string, equivalent_minor: int, equivalent_currency: string}|null
     */
    public function snapshot(Organization $organization, string $currency, int $amountMinor): ?array
    {
        $rate = ExchangeRate::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where(function ($query) use ($currency) {
                $query->where('base_currency', $currency)->orWhere('quote_currency', $currency);
            })
            ->orderByDesc('effective_at')
            ->orderByDesc('id')
            ->first();

        if ($rate === null) {
            return null;
        }

        if ($currency === $rate->base_currency) {
            $equivalentCurrency = $rate->quote_currency;
            $equivalent = Money::convertMinor($amountMinor, (string) $rate->rate);
        } else {
            $equivalentCurrency = $rate->base_currency;
            $equivalent = Money::convertMinorInverse($amountMinor, (string) $rate->rate);
        }

        return [
            'exchange_rate_id' => $rate->id,
            'fx_base_currency' => $rate->base_currency,
            'fx_quote_currency' => $rate->quote_currency,
            'fx_rate' => (string) $rate->rate,
            'equivalent_minor' => $equivalent,
            'equivalent_currency' => $equivalentCurrency,
        ];
    }

    public function appliesTo(Contract $contract): bool
    {
        return $contract->fx_rate !== null;
    }
}
