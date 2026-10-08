<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CashCollectionStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashCollection extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'tenant_id', 'agent_id', 'invoice_id', 'payment_id',
        'reference', 'code', 'amount_minor', 'currency', 'status',
        'tenant_confirmed_at', 'agent_confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CashCollectionStatus::class,
            'amount_minor' => 'integer',
            'tenant_confirmed_at' => 'datetime',
            'agent_confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (CashCollection $collection) {
            if ($collection->isDirty('amount_minor') || $collection->isDirty('currency')) {
                throw new DomainException('Le montant d\'un encaissement est immuable.');
            }
        });

        static::deleting(fn () => throw new DomainException('Un encaissement ne peut pas être supprimé.'));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
