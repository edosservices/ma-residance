<?php

declare(strict_types=1);

namespace App\Enums;

enum ExpenseStatus: string
{
    case Recorded = 'recorded';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Recorded => 'Enregistrée',
            self::Voided => 'Annulée',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Recorded => 'info',
            self::Voided => 'neutral',
        };
    }
}
