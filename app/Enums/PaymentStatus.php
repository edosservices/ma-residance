<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'À confirmer',
            self::Approved => 'Approuvé',
            self::Rejected => 'Rejeté',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Approved => 'good',
            self::Pending => 'warn',
            self::Rejected => 'bad',
        };
    }
}
