<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UnitStatus;
use App\Enums\UnitType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'property_id', 'name', 'reference', 'type', 'description',
        'bedrooms', 'features', 'price_minor', 'currency', 'status', 'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'type' => UnitType::class,
            'status' => UnitStatus::class,
            'features' => 'array',
            'price_minor' => 'integer',
            'bedrooms' => 'integer',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(UnitPriceHistory::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
