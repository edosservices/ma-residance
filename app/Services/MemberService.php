<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MemberRole;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Support\DomainException;
use App\Support\Phone;
use App\Support\RoleMatrix;
use Illuminate\Support\Facades\DB;

class MemberService
{
    public function __construct(private AuditLogger $audit) {}

    public function create(Organization $organization, User $actor, array $data): OrganizationMember
    {
        $role = MemberRole::from($data['role']);

        if ($role === MemberRole::Owner) {
            throw new DomainException('Le bailleur principal se crée à l\'inscription.');
        }

        return DB::transaction(function () use ($organization, $actor, $data, $role) {
            $phone = Phone::normalize($data['phone']);
            $user = User::query()->where('phone', $phone)->first();

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $data['name'],
                    'phone' => $phone,
                    'email' => $data['email'] ?? null,
                    'password' => $data['password'],
                    'status' => UserStatus::Active,
                    'timezone' => $organization->timezone,
                    'locale' => 'fr',
                ]);
            }

            if (OrganizationMember::withoutGlobalScopes()->where('organization_id', $organization->id)->where('user_id', $user->id)->exists()) {
                throw new DomainException('Cette personne fait déjà partie de l\'organisation.');
            }

            $permissions = RoleMatrix::intersect($role, $data['permissions'] ?? RoleMatrix::defaults($role));

            $member = OrganizationMember::withoutGlobalScopes()->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => $role,
                'permissions' => $permissions,
                'status' => 'active',
            ]);

            $this->audit->log($organization->id, $actor, 'member.created', $member, "A ajouté {$user->name} comme {$role->label()}.");

            return $member;
        });
    }

    public function updatePermissions(OrganizationMember $member, User $actor, array $permissions): OrganizationMember
    {
        if ($member->role === MemberRole::Owner) {
            throw new DomainException('Les droits du bailleur principal ne se restreignent pas.');
        }

        $member->permissions = RoleMatrix::intersect($member->role, $permissions);
        $member->save();
        $member->load('user');
        $this->audit->log($member->organization_id, $actor, 'member.permissions', $member, 'A modifié les permissions de '.$member->user->name.'.', ['permissions' => $member->permissions]);

        return $member;
    }

    public function suspend(OrganizationMember $member, User $actor, bool $active): void
    {
        if ($member->role === MemberRole::Owner) {
            throw new DomainException('Le bailleur principal ne peut pas être retiré ici.');
        }

        $member->status = $active ? 'active' : 'suspended';
        $member->save();
        $this->audit->log($member->organization_id, $actor, 'member.status', $member, ($active ? 'A réactivé ' : 'A suspendu ').$member->user->name.'.');
    }
}
