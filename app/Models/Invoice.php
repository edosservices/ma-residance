<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'contract_id', 'tenant_id', 'unit_id', 'property_id',
        'utility_charge_id', 'number', 'type', 'period_key', 'dedupe_key',
        'period_start', 'period_end', 'amount_minor', 'currency', 'fx_rate',
        'due_on', 'status', 'issued_at', 'notes', 'cancelled_at', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'due_on' => 'date',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'amount_minor' => 'integer',
            'fx_rate' => 'decimal:8',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Invoice $invoice) {
            if ($invoice->isDirty('amount_minor') || $invoice->isDirty('currency')) {
                throw new DomainException('Le montant d\'une facture est immuable.');
            }
        });

        static::deleting(fn () => throw new DomainException('Une facture ne peut pas être supprimée.'));
    }

    public function scopeWithBalance(Builder $query): Builder
    {
        return $query
            ->withSum(['allocations as paid_minor' => function ($q) {
                $q->whereHas('payment', fn ($p) => $p->where('status', 'approved')->where('kind', 'payment'));
            }], 'amount_minor')
            ->withSum(['allocations as reversed_minor' => function ($q) {
                $q->whereHas('payment', fn ($p) => $p->where('status', 'approved')->where('kind', 'reversal'));
            }], 'amount_minor');
    }

    public function netPaidMinor(): int
    {
        if (! array_key_exists('paid_minor', $this->attributes)) {
            $this->loadSum(['allocations as paid_minor' => function ($q) {
                $q->whereHas('payment', fn ($p) => $p->where('status', 'approved')->where('kind', 'payment'));
            }], 'amount_minor');
            $this->loadSum(['allocations as reversed_minor' => function ($q) {
                $q->whereHas('payment', fn ($p) => $p->where('status', 'approved')->where('kind', 'reversal'));
            }], 'amount_minor');
        }

        return (int) ($this->attributes['paid_minor'] ?? 0) - (int) ($this->attributes['reversed_minor'] ?? 0);
    }

    public function balanceMinor(): int
    {
        return max(0, $this->amount_minor - $this->netPaidMinor());
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
