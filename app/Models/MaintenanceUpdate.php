<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceUpdate extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'maintenance_request_id', 'user_id', 'status_to', 'note', 'photo_path', 'expense_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
