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
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', [
            'organizations' => Organization::query()->count(),
            'activeOrganizations' => Organization::query()->where('status', OrganizationStatus::Active)->count(),
            'users' => User::query()->count(),
            'suspendedUsers' => User::query()->where('status', UserStatus::Suspended)->count(),
            'approvedPayments' => Payment::withoutGlobalScopes()->where('status', 'approved')->where('kind', 'payment')->count(),
        ]);
    }

    public function organizations()
    {
        $organizations = Organization::query()->withCount('members')->latest()->paginate(20);

        return view('admin.organizations', compact('organizations'));
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
