<?php

declare(strict_types=1);

namespace App\Enums;

enum ContractStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case MoveOutRequested = 'move_out_requested';
    case Ended = 'ended';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Pending => 'En attente',
            self::Active => 'Actif',
            self::MoveOutRequested => 'Départ demandé',
            self::Ended => 'Terminé',
            self::Cancelled => 'Annulé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'good',
            self::Pending, self::MoveOutRequested, self::Draft => 'warn',
            self::Ended => 'neutral',
            self::Cancelled => 'bad',
        };
    }
}
