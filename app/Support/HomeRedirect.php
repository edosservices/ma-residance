<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class HomeRedirect
{
    public static function for(User $user): RedirectResponse
    {
        $member = OrganizationMember::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($member) {
            $organization = Organization::query()->find($member->organization_id);

            if ($organization && $organization->status === OrganizationStatus::Active) {
                session(['current_organization_id' => $organization->id]);

                return redirect()->route('office.dashboard');
            }
        }

        $tenant = Tenant::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($tenant) {
            session(['current_tenant_id' => $tenant->id]);

            return redirect()->route('portal.dashboard');
        }

        if ($user->is_super_admin) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('catalog.index');
    }
}
