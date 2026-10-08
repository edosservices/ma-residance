<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Permission;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Notifications\DomainNotification;

class NotificationDispatcher
{
    public function notify(User $user, string $kind, string $title, string $body, ?string $url = null): void
    {
        $user->notify(new DomainNotification($kind, $title, $body, $url));
    }

    public function notifyMembers(
        Organization $organization,
        Permission $permission,
        string $kind,
        string $title,
        string $body,
        ?string $url = null,
    ): void {
        $members = OrganizationMember::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->with('user')
            ->get();

        foreach ($members as $member) {
            if ($member->user && $member->hasPermission($permission)) {
                $this->notify($member->user, $kind, $title, $body, $url);
            }
        }
    }

    /**
     * @param  iterable<User>  $users
     */
    public function notifyUsers(iterable $users, string $kind, string $title, string $body, ?string $url = null): void
    {
        $seen = [];

        foreach ($users as $user) {
            if (isset($seen[$user->id])) {
                continue;
            }

            $seen[$user->id] = true;
            $this->notify($user, $kind, $title, $body, $url);
        }
    }
}
