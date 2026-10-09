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
            $actorMember = $this->actorMember($organization->id, $actor);

            if (! in_array($role, RoleMatrix::creatable($actorMember->role), true)) {
                throw new DomainException('Le gérant ne peut créer que son agent, avec moins de droits.');
            }

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

            $grantable = RoleMatrix::grantable($actorMember->role, $role);
            $requested = $data['permissions'] ?? ($actorMember->role === MemberRole::Manager ? $grantable : RoleMatrix::defaults($role));
            $permissions = array_values(array_intersect($grantable, $requested));

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

        $actorMember = $this->actorMember($member->organization_id, $actor);
        $this->assertCanManage($actorMember, $member);
        $grantable = RoleMatrix::grantable($actorMember->role, $member->role);
        $kept = array_values(array_filter(
            $member->permissions ?? [],
            fn (string $permission) => ! in_array($permission, $grantable, true),
        ));
        $member->permissions = array_values(array_unique([
            ...$kept,
            ...array_intersect($grantable, $permissions),
        ]));
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

        $actorMember = $this->actorMember($member->organization_id, $actor);
        $this->assertCanManage($actorMember, $member);

        $member->status = $active ? 'active' : 'suspended';
        $member->save();
        $this->audit->log($member->organization_id, $actor, 'member.status', $member, ($active ? 'A réactivé ' : 'A suspendu ').$member->user->name.'.');
    }

    private function actorMember(int $organizationId, User $actor): OrganizationMember
    {
        $member = OrganizationMember::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('user_id', $actor->id)
            ->first();

        if ($member === null) {
            throw new DomainException('Seul un membre de l\'organisation peut gérer l\'équipe.');
        }

        return $member;
    }

    private function assertCanManage(OrganizationMember $actor, OrganizationMember $target): void
    {
        if ($actor->role === MemberRole::Owner) {
            return;
        }

        if ($actor->role === MemberRole::Manager && $target->role === MemberRole::Collector) {
            return;
        }

        throw new DomainException('Le gérant ne peut ajuster que son agent.');
    }
}
