<?php

declare(strict_types=1);

namespace App\Enums;

enum UnitType: string
{
    case Room = 'room';
    case Studio = 'studio';
    case Apartment = 'apartment';
    case House = 'house';
    case Office = 'office';

    public function label(): string
    {
        return (string) config('residence.unit_types.'.$this->value, $this->value);
    }
}
