<?php

declare(strict_types=1);

namespace App\Services\Payments;

interface PaymentChannel
{
    public function code(): string;

    public function label(): string;

    public function automated(): bool;
}
