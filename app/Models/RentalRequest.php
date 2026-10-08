<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RentalRequestStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalRequest extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'unit_id', 'user_id', 'tenant_id', 'message', 'status',
        'decided_by', 'decided_at', 'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => RentalRequestStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
