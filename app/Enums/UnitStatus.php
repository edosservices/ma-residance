<?php

declare(strict_types=1);

namespace App\Enums;

enum UnitStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Pending = 'pending';
    case Occupied = 'occupied';
    case DepartureScheduled = 'departure_scheduled';
    case Maintenance = 'maintenance';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Reserved => 'Réservé',
            self::Pending => 'En attente',
            self::Occupied => 'Occupé',
            self::DepartureScheduled => 'Départ programmé',
            self::Maintenance => 'Maintenance',
            self::Unavailable => 'Indisponible',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Available => 'good',
            self::Occupied, self::DepartureScheduled => 'info',
            self::Reserved, self::Pending, self::Maintenance => 'warn',
            self::Unavailable => 'neutral',
        };
    }
}
