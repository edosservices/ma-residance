<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MemberRole;
use App\Enums\Permission;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\RoleMatrix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationMember extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'user_id', 'role', 'permissions', 'status',
    ];

    protected function casts(): array
    {
        return [
            'role' => MemberRole::class,
            'permissions' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasPermission(Permission $permission): bool
    {
        if ($this->role === MemberRole::Owner) {
            return true;
        }

        if (! in_array($permission->value, RoleMatrix::cap($this->role), true)) {
            return false;
        }

        $granted = $this->permissions ?? RoleMatrix::defaults($this->role);

        return in_array($permission->value, $granted, true);
    }
}
