<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRemittanceItem extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'cash_remittance_id', 'cash_collection_id', 'amount_minor',
    ];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer'];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(CashCollection::class, 'cash_collection_id');
    }

    public function remittance(): BelongsTo
    {
        return $this->belongsTo(CashRemittance::class, 'cash_remittance_id');
    }
}
