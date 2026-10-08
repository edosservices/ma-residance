<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RemittanceStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRemittance extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'agent_id', 'reference', 'amount_minor', 'currency',
        'status', 'note', 'confirmed_by', 'confirmed_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => RemittanceStatus::class,
            'amount_minor' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => throw new DomainException('Une remise de fonds ne peut pas être supprimée.'));
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CashRemittanceItem::class);
    }
}
