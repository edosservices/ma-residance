<?php

declare(strict_types=1);

namespace App\Enums;

enum MaintenanceStatus: string
{
    case Reported = 'reported';
    case Received = 'received';
    case Verifying = 'verifying';
    case Confirmed = 'confirmed';
    case Quoted = 'quoted';
    case Approved = 'approved';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Reported => 'Signalé',
            self::Received => 'Reçu',
            self::Verifying => 'En vérification',
            self::Confirmed => 'Confirmé',
            self::Quoted => 'Devis',
            self::Approved => 'Approuvé',
            self::InProgress => 'En intervention',
            self::Done => 'Terminé',
            self::Cancelled => 'Annulé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Done => 'good',
            self::Cancelled => 'neutral',
            self::Reported, self::Received => 'info',
            default => 'warn',
        };
    }

    /**
     * @return list<self>
     */
    public function next(): array
    {
        return match ($this) {
            self::Reported => [self::Received, self::Cancelled],
            self::Received => [self::Verifying, self::Cancelled],
            self::Verifying => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Quoted, self::Cancelled],
            self::Quoted => [self::Approved, self::Cancelled],
            self::Approved => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Done, self::Cancelled],
            self::Done, self::Cancelled => [],
        };
    }
}
