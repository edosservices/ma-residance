<?php

declare(strict_types=1);

namespace App\Enums;

enum MaintenanceUrgency: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Faible',
            self::Normal => 'Normale',
            self::High => 'Urgente',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Low => 'neutral',
            self::Normal => 'info',
            self::High => 'bad',
        };
    }
}
