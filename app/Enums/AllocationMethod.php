<?php

declare(strict_types=1);

namespace App\Enums;

enum AllocationMethod: string
{
    case PerPerson = 'per_person';
    case PerUnit = 'per_unit';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::PerPerson => 'Par personne',
            self::PerUnit => 'Par logement',
            self::Fixed => 'Montant fixe par logement occupé',
        };
    }
}
