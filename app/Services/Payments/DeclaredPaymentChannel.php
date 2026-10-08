<?php

declare(strict_types=1);

namespace App\Services\Payments;

final class DeclaredPaymentChannel implements PaymentChannel
{
    public function code(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return 'Paiement déclaré';
    }

    public function automated(): bool
    {
        return false;
    }
}
