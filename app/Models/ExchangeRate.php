<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Support\DomainException;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'base_currency', 'quote_currency', 'rate', 'effective_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'effective_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new DomainException('Un taux de change enregistré ne peut pas être modifié.'));
        static::deleting(fn () => throw new DomainException('Un taux de change enregistré ne peut pas être supprimé.'));
    }
}
