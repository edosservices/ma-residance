<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecognitionDeed extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'contract_id', 'reference', 'payee_name',
        'deposit_months', 'advance_months', 'amount_minor', 'currency',
        'identity_document', 'identity_path', 'origin', 'premises',
        'landlord_witnesses', 'tenant_witnesses', 'certified_at',
        'certificate_code', 'certificate_holder', 'certificate_path',
        'content_hash', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'deposit_months' => 'integer',
            'advance_months' => 'integer',
            'amount_minor' => 'integer',
            'certified_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function months(): int
    {
        return $this->deposit_months + $this->advance_months;
    }

    public function isCertified(): bool
    {
        return $this->certified_at !== null;
    }
}
