<?php

declare(strict_types=1);

namespace App\Services\Payments;

final class PaymentChannelRegistry
{
    /**
     * @param  array<string, PaymentChannel>  $channels
     */
    public function __construct(private array $channels) {}

    /**
     * @return array<string, PaymentChannel>
     */
    public function all(): array
    {
        return $this->channels;
    }

    public function get(string $code): ?PaymentChannel
    {
        return $this->channels[$code] ?? null;
    }
}
