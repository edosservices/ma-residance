<?php

declare(strict_types=1);

namespace App\Enums;

enum CashCollectionStatus: string
{
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Confirmed = 'confirmed';
    case RemittancePending = 'remittance_pending';
    case Remitted = 'remitted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingConfirmation => 'En attente des confirmations',
            self::Confirmed => 'Encaissé, à remettre',
            self::RemittancePending => 'Remise en cours',
            self::Remitted => 'Remis au bailleur',
            self::Cancelled => 'Annulé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Remitted => 'good',
            self::Confirmed, self::RemittancePending => 'warn',
            self::AwaitingConfirmation => 'info',
            self::Cancelled => 'neutral',
        };
    }
}
