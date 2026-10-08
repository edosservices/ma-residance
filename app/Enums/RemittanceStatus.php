<?php

declare(strict_types=1);

namespace App\Enums;

enum RemittanceStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente du bailleur',
            self::Confirmed => 'Reçue',
            self::Rejected => 'Refusée',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Confirmed => 'good',
            self::Pending => 'warn',
            self::Rejected => 'bad',
        };
    }
}
