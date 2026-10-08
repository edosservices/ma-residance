<?php

declare(strict_types=1);

namespace App\Http\Controllers\Office;

use App\Enums\MemberRole;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Models\OrganizationMember;
use App\Models\Tenant;
use App\Services\AuditLogger;
use App\Services\Billing\ProrataManager;
use App\Services\ExchangeRateService;
use App\Services\MemberService;
use App\Services\TenantService;
use App\Support\CurrentContext;
use App\Support\Money;
use App\Support\RoleMatrix;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PeopleController extends Controller
{
    public function tenants(Request $request)
    {
        $tenants = Tenant::query()
            ->when($request->string('q')->toString(), fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('office.tenants.index', compact('tenants'));
    }

    public function storeTenant(Request $request, CurrentContext $context, TenantService $tenants)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:160'],
            'occupants' => ['nullable', 'integer', 'min:1', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $tenants->create($context->organization(), $request->user(), $data);

        return back()->with('status', 'Locataire enregistré.');
    }

    public function showTenant(Tenant $tenant)
    {
        $this->authorize('view', $tenant);
        $tenant->load(['contracts.unit', 'invoices' => fn ($query) => $query->latest()->limit(12)]);

        return view('office.tenants.show', compact('tenant'));
    }

    public function members()
    {
        $members = OrganizationMember::query()->with('user')->orderBy('role')->get();

        return view('office.members.index', [
            'members' => $members,
            'roles' => [MemberRole::Manager, MemberRole::Collector, MemberRole::Accountant, MemberRole::Technician],
            'matrix' => collect(MemberRole::cases())->mapWithKeys(fn (MemberRole $role) => [$role->value => RoleMatrix::cap($role)]),
            'permissions' => Permission::cases(),
        ]);
    }

    public function storeMember(Request $request, CurrentContext $context, MemberService $members)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:160'],
            'password' => ['required', Password::min(8)],
            'role' => ['required', Rule::in(['manager', 'collector', 'accountant', 'technician'])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);
        $members->create($context->organization(), $request->user(), $data);

        return back()->with('status', 'Collaborateur ajouté.');
    }

    public function updateMember(Request $request, OrganizationMember $member, MemberService $members)
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'active' => ['nullable', 'boolean'],
        ]);

        if ($request->has('active')) {
            $members->suspend($member, $request->user(), $request->boolean('active'));
        }

        if ($request->has('permissions')) {
            $members->updatePermissions($member, $request->user(), $data['permissions'] ?? []);
        }

        return back()->with('status', 'Équipe mise à jour.');
    }

    public function settings(CurrentContext $context, ProrataManager $prorata)
    {
        return view('office.settings', [
            'organization' => $context->organization(),
            'prorata' => $prorata->options(),
            'rates' => ExchangeRate::query()->latest('effective_at')->limit(8)->get(),
        ]);
    }

    public function updateSettings(Request $request, CurrentContext $context, AuditLogger $audit)
    {
        $data = $request->validate([
            'generation_day' => ['required', 'integer', 'min:1', 'max:28'],
            'due_day' => ['required', 'integer', 'min:1', 'max:31'],
            'grace_until_day' => ['required', 'integer', 'min:1', 'max:28'],
            'prorata_method' => ['required', 'string'],
            'reminder_days_before' => ['required', 'integer', 'min:1', 'max:15'],
            'reminder_repeat_days' => ['required', 'integer', 'min:1', 'max:30'],
            'default_currency' => ['required', Rule::in($context->organization()->currencies())],
        ]);

        $organization = $context->organization();
        $organization->settings = array_merge($organization->settings ?? [], $data);
        $organization->save();
        $audit->log($organization->id, $request->user(), 'settings.updated', $organization, 'A modifié les paramètres de facturation.');

        return back()->with('status', 'Paramètres enregistrés.');
    }

    public function storeRate(Request $request, CurrentContext $context, ExchangeRateService $rates)
    {
        $data = $request->validate([
            'base_currency' => ['required', 'string', 'size:3'],
            'quote_currency' => ['required', 'string', 'size:3'],
            'rate' => ['required', 'string', 'max:20'],
        ]);
        $rates->record($context->organization(), $request->user(), $data['base_currency'], $data['quote_currency'], Money::normalizeRate($data['rate']));

        return back()->with('status', 'Taux enregistré. Les anciens contrats conservent leur taux.');
    }
}
