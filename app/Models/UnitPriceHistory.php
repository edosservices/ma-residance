<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitPriceHistory extends Model
{
    protected $fillable = [
        'unit_id', 'price_minor', 'currency', 'effective_at', 'changed_by', 'note',
    ];

    protected function casts(): array
    {
        return [
            'effective_at' => 'datetime',
            'price_minor' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new DomainException('L\'historique de prix est immuable.'));
        static::deleting(fn () => throw new DomainException('L\'historique de prix est immuable.'));
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
