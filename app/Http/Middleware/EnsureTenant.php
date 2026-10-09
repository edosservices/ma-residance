<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\OrganizationStatus;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Tenant;
use App\Support\CurrentContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    public function __construct(private CurrentContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isActive()) {
            abort(403);
        }

        $preferred = $request->session()->get('current_tenant_id');
        $query = Tenant::withoutGlobalScopes()->where('user_id', $user->id)->where('status', 'active');
        $tenant = $preferred ? (clone $query)->whereKey($preferred)->first() : null;
        $tenant ??= $query->first();

        if ($tenant === null) {
            return redirect()->route('catalog.index')->with('error', 'Vous n\'avez pas encore de logement.');
        }

        $organization = Organization::query()->find($tenant->organization_id);

        if ($organization === null || $organization->status !== OrganizationStatus::Active) {
            abort(403, 'Cette organisation est suspendue.');
        }

        $request->session()->put('current_tenant_id', $tenant->id);
        $this->context->setOrganization($organization);
        $this->context->setTenant($tenant);
        $contract = Contract::query()
            ->with('unit.property')
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'move_out_requested', 'pending'])
            ->latest('start_date')
            ->first();
        $portalContractLine = $contract
            ? trim(implode(' · ', array_filter([
                $contract->unit?->property?->name,
                $contract->unit?->name,
                $contract->reference,
            ])))
            : null;
        view()->share([
            'shellNav' => 'partials.nav-portal',
            'home' => route('portal.dashboard'),
            'alerts' => route('portal.notifications.index'),
            'eyebrow' => 'Espace locataire',
            'shellRole' => 'portal',
            'shellTheme' => 'tenant',
            'portalContractLine' => $portalContractLine,
        ]);

        return $next($request);
    }
}
