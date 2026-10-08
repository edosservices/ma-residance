<?php

declare(strict_types=1);

namespace App\Enums;

enum RentalRequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Refused = 'refused';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Accepted => 'Acceptée',
            self::Refused => 'Refusée',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Accepted => 'good',
            self::Refused => 'bad',
        };
    }
}
