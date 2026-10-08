<?php

declare(strict_types=1);

namespace App\Enums;

enum MemberRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Collector = 'collector';
    case Accountant = 'accountant';
    case Technician = 'technician';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Bailleur principal',
            self::Manager => 'Gérant',
            self::Collector => 'Agent de recouvrement',
            self::Accountant => 'Comptable',
            self::Technician => 'Technicien',
        };
    }
}
