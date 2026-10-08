<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceType: string
{
    case Rent = 'rent';
    case Water = 'water';
    case Electricity = 'electricity';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Rent => 'Loyer',
            self::Water => 'Eau',
            self::Electricity => 'Électricité',
            self::Other => 'Autre charge',
        };
    }
}
