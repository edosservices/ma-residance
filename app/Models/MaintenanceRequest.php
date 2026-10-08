<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceUrgency;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceRequest extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'unit_id', 'tenant_id', 'reported_by', 'assigned_to',
        'title', 'description', 'urgency', 'status', 'photo_path',
        'estimated_cost_minor', 'currency', 'quote_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => MaintenanceStatus::class,
            'urgency' => MaintenanceUrgency::class,
            'estimated_cost_minor' => 'integer',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(MaintenanceUpdate::class);
    }
}
