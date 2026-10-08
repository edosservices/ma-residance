<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\RegistrationService;
use App\Support\DomainException;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', [
            'organizations' => Organization::query()->count(),
            'activeOrganizations' => Organization::query()->where('status', OrganizationStatus::Active)->count(),
            'suspendedOrganizations' => Organization::query()->where('status', OrganizationStatus::Suspended)->count(),
            'users' => User::query()->count(),
            'suspendedUsers' => User::query()->where('status', UserStatus::Suspended)->count(),
            'tenants' => Tenant::withoutGlobalScopes()->count(),
            'properties' => Property::withoutGlobalScopes()->count(),
            'units' => Unit::withoutGlobalScopes()->count(),
            'approvedPayments' => Payment::withoutGlobalScopes()->where('status', 'approved')->where('kind', 'payment')->count(),
            'recentOrganizations' => Organization::query()->withCount(['properties', 'units', 'tenants'])->latest()->limit(6)->get(),
            'activity' => AuditLog::withoutGlobalScopes()->with('user')->latest('id')->limit(8)->get(),
        ]);
    }

    public function organizations()
    {
        $organizations = Organization::query()->withCount(['members', 'properties', 'units', 'tenants'])->latest()->paginate(20);

        return view('admin.organizations', compact('organizations'));
    }

    public function storeOrganization(Request $request, RegistrationService $registration, AuditLogger $audit)
    {
        try {
            $request->merge(['phone' => Phone::normalize((string) $request->input('phone'))]);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['phone' => $exception->getMessage()]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'organization_name' => ['required', 'string', 'max:160'],
        ]);

        $user = $registration->registerLandlord(
            $data['name'],
            $data['phone'],
            $data['email'] ?? null,
            $data['password'],
            $data['organization_name'],
        );

        $audit->log(null, $request->user(), 'organization.created', $user, 'A créé le compte bailleur '.$user->name.' pour '.$data['organization_name'].'.');

        return back()->with('status', 'Compte bailleur créé. Il peut se connecter avec son téléphone.');
    }

    public function organizationStatus(Organization $organization, AuditLogger $audit, Request $request)
    {
        $organization->status = $organization->status === OrganizationStatus::Active
            ? OrganizationStatus::Suspended
            : OrganizationStatus::Active;
        $organization->save();
        $audit->log(null, $request->user(), 'organization.status', $organization, 'A passé '.$organization->name.' au statut '.$organization->status->label().'.');

        return back()->with('status', 'Statut de l\'organisation mis à jour.');
    }

    public function users()
    {
        $users = User::query()->latest()->paginate(30);

        return view('admin.users', compact('users'));
    }

    public function userStatus(User $user, Request $request, AuditLogger $audit)
    {
        abort_if($user->is_super_admin, 403);
        $user->status = $user->status === UserStatus::Active ? UserStatus::Suspended : UserStatus::Active;
        $user->save();
        $audit->log(null, $request->user(), 'user.status', $user, 'A passé '.$user->name.' au statut '.$user->status->label().'.');

        return back()->with('status', 'Statut de l\'utilisateur mis à jour.');
    }

    public function audit()
    {
        $logs = AuditLog::withoutGlobalScopes()->with('user')->latest('id')->paginate(40);

        return view('admin.audit', compact('logs'));
    }

    public function settings()
    {
        $settings = PlatformSetting::query()->pluck('value', 'key');

        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'platform_name' => ['required', 'string', 'max:120'],
            'support_phone' => ['nullable', 'string', 'max:30'],
        ]);

        foreach ($data as $key => $value) {
            PlatformSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => ['text' => $value], 'updated_by' => $request->user()->id],
            );
        }

        $audit->log(null, $request->user(), 'platform.settings', null, 'A modifié les paramètres globaux.');

        return back()->with('status', 'Paramètres globaux enregistrés.');
    }
}
