<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Support\DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'payment_id', 'invoice_id', 'amount_minor',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new DomainException('Une affectation de paiement est immuable.'));
        static::deleting(fn () => throw new DomainException('Une affectation de paiement est immuable.'));
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
