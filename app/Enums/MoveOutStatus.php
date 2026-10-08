<?php

declare(strict_types=1);

namespace App\Enums;

enum MoveOutStatus: string
{
    case Requested = 'requested';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Demandé',
            self::Completed => 'Validé',
            self::Rejected => 'Refusé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Requested => 'warn',
            self::Completed => 'good',
            self::Rejected => 'neutral',
        };
    }
}
