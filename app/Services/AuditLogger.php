<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public function log(
        ?int $organizationId,
        ?User $actor,
        string $action,
        ?Model $subject,
        string $description,
        array $properties = [],
    ): AuditLog {
        $request = request();

        return AuditLog::withoutGlobalScopes()->create([
            'organization_id' => $organizationId,
            'user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 1000) : null,
            'created_at' => now(),
        ]);
    }
}
