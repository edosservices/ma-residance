<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Open = 'open';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'À payer',
            self::Partial => 'Partiel',
            self::Paid => 'Payé',
            self::Overdue => 'En retard',
            self::Cancelled => 'Annulé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Paid => 'good',
            self::Open => 'info',
            self::Partial => 'warn',
            self::Overdue => 'bad',
            self::Cancelled => 'neutral',
        };
    }
}
