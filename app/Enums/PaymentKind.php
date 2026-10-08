<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentKind: string
{
    case Payment = 'payment';
    case Reversal = 'reversal';

    public function label(): string
    {
        return match ($this) {
            self::Payment => 'Paiement',
            self::Reversal => 'Annulation',
        };
    }
}
