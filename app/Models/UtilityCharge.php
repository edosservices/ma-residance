<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AllocationMethod;
use App\Enums\InvoiceType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UtilityCharge extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'property_id', 'type', 'period_key', 'period_start', 'period_end',
        'total_minor', 'currency', 'method', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'method' => AllocationMethod::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'total_minor' => 'integer',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
