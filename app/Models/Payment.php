<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'tenant_id', 'contract_id', 'invoice_id', 'reference',
        'provider', 'provider_reference', 'kind', 'reverses_payment_id', 'amount_minor',
        'currency', 'method', 'status', 'proof_path', 'note', 'rejection_reason',
        'declared_by', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => PaymentKind::class,
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount_minor' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Payment $payment) {
            foreach (['amount_minor', 'currency', 'tenant_id', 'invoice_id', 'kind', 'method'] as $field) {
                if ($payment->isDirty($field)) {
                    throw new DomainException('Un paiement validé ou déclaré ne peut pas être modifié dans son montant.');
                }
            }
        });

        static::deleting(fn () => throw new DomainException('Un paiement ne peut pas être supprimé.'));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reverses_payment_id');
    }

    public function declarer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declared_by');
    }
}
