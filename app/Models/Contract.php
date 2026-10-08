<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContractStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'property_id', 'unit_id', 'tenant_id', 'reference',
        'start_date', 'end_date', 'rent_minor', 'currency', 'exchange_rate_id',
        'fx_base_currency', 'fx_quote_currency', 'fx_rate', 'equivalent_minor',
        'equivalent_currency', 'billing_cycle', 'generation_day', 'due_day',
        'grace_until_day', 'prorata_method', 'conditions', 'status', 'activated_at',
        'terminated_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'rent_minor' => 'integer',
            'equivalent_minor' => 'integer',
            'fx_rate' => 'decimal:8',
            'status' => ContractStatus::class,
            'activated_at' => 'datetime',
            'terminated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Contract $contract) {
            foreach (['rent_minor', 'currency', 'fx_rate', 'fx_base_currency', 'fx_quote_currency', 'equivalent_minor', 'equivalent_currency'] as $field) {
                if (! $contract->isDirty($field)) {
                    continue;
                }

                $original = $contract->getOriginal($field);
                $current = $contract->getAttribute($field);

                if ($field === 'fx_rate' && $original !== null && $current !== null && bccomp((string) $original, (string) $current, 8) === 0) {
                    continue;
                }

                throw new DomainException('Le montant historique d\'un contrat ne peut pas être modifié.');
            }
        });

        static::deleting(fn () => throw new DomainException('Un contrat ne peut pas être supprimé.'));
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function moveOutRequests(): HasMany
    {
        return $this->hasMany(MoveOutRequest::class);
    }
}
