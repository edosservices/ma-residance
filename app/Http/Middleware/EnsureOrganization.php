<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\MemberRole;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Support\CurrentContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganization
{
    public function __construct(private CurrentContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isActive()) {
            abort(403, 'Compte indisponible.');
        }

        $preferred = $request->session()->get('current_organization_id');
        $memberships = OrganizationMember::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('status', 'active');

        $member = $preferred
            ? (clone $memberships)->where('organization_id', $preferred)->first()
            : null;
        $member ??= $memberships->first();

        if ($member === null) {
            abort(403, 'Aucune organisation.');
        }

        $organization = Organization::query()->find($member->organization_id);

        if ($organization === null || $organization->status !== OrganizationStatus::Active) {
            abort(403, 'Cette organisation est suspendue.');
        }

        $request->session()->put('current_organization_id', $organization->id);
        $this->context->setOrganization($organization);
        $this->context->setMember($member);
        view()->share([
            'shellNav' => 'partials.nav-office',
            'home' => route('office.dashboard'),
            'alerts' => route('office.notifications.index'),
            'eyebrow' => $member->role->label(),
            'shellRole' => 'office',
            'shellTheme' => $member->role === MemberRole::Collector ? 'collector' : 'landlord',
        ]);

        return $next($request);
    }
}
