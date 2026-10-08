<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;

class TenantService
{
    public function __construct(private AuditLogger $audit) {}

    public function create(Organization $organization, User $actor, array $data): Tenant
    {
        return DB::transaction(function () use ($organization, $actor, $data) {
            $phone = Phone::normalize($data['phone']);
            $existing = Tenant::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('phone', $phone)
                ->first();

            if ($existing) {
                return $existing;
            }

            $user = User::query()->where('phone', $phone)->first();

            $tenant = Tenant::withoutGlobalScopes()->create([
                'organization_id' => $organization->id,
                'user_id' => $user?->id,
                'name' => $data['name'],
                'phone' => $phone,
                'email' => isset($data['email']) && $data['email'] !== '' ? mb_strtolower($data['email']) : null,
                'occupants' => max(1, (int) ($data['occupants'] ?? 1)),
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
            ]);

            $this->audit->log($organization->id, $actor, 'tenant.created', $tenant, "A ajouté le locataire {$tenant->name}.");

            return $tenant;
        });
    }

    public function ensureForUser(Organization $organization, User $user, ?string $name = null): Tenant
    {
        $tenant = Tenant::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)->orWhere('phone', $user->phone);
            })
            ->first();

        if ($tenant) {
            if ($tenant->user_id === null) {
                $tenant->user_id = $user->id;
                $tenant->save();
            }

            return $tenant;
        }

        return Tenant::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'name' => $name ?: $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'occupants' => 1,
            'status' => 'active',
        ]);
    }
}
