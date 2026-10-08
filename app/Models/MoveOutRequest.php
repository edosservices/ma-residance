<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MoveOutStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoveOutRequest extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'contract_id', 'tenant_id', 'requested_by', 'requested_on',
        'planned_on', 'reason', 'status', 'checklist', 'photo_path', 'review_note',
        'reviewed_by', 'completed_at', 'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MoveOutStatus::class,
            'requested_on' => 'date',
            'planned_on' => 'date',
            'checklist' => 'array',
            'completed_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
