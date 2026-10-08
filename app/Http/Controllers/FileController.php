<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceUpdate;
use App\Models\Message;
use App\Models\MoveOutRequest;
use App\Models\OrganizationMember;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function show(Request $request, string $path)
    {
        $path = str_replace('\\', '/', $path);

        if (str_contains($path, '..') || ! preg_match('#^(proofs|maintenance|expenses|messages|moveouts|properties|units)/#', $path)) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $organizationId = $this->organizationId($path);

        if ($organizationId === null) {
            abort(404);
        }

        $user = $request->user();
        $member = OrganizationMember::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->exists();

        if (! $member && ! $user->is_super_admin && ! $this->tenantOwns($user->id, $organizationId, $path)) {
            abort(403);
        }

        return Storage::disk('local')->response($path);
    }

    private function organizationId(string $path): ?int
    {
        return Payment::withoutGlobalScopes()->where('proof_path', $path)->value('organization_id')
            ?? Expense::withoutGlobalScopes()->where('attachment_path', $path)->value('organization_id')
            ?? MaintenanceRequest::withoutGlobalScopes()->where('photo_path', $path)->value('organization_id')
            ?? MaintenanceUpdate::withoutGlobalScopes()->where('photo_path', $path)->value('organization_id')
            ?? Message::withoutGlobalScopes()->where('attachment_path', $path)->value('organization_id')
            ?? MoveOutRequest::withoutGlobalScopes()->where('photo_path', $path)->value('organization_id')
            ?? Property::withoutGlobalScopes()->where('photo_path', $path)->value('organization_id')
            ?? Unit::withoutGlobalScopes()->where('photo_path', $path)->value('organization_id');
    }

    private function tenantOwns(int $userId, int $organizationId, string $path): bool
    {
        $tenantIds = Tenant::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('organization_id', $organizationId)
            ->pluck('id');

        if ($tenantIds->isEmpty()) {
            return false;
        }

        return Payment::withoutGlobalScopes()->where('proof_path', $path)->whereIn('tenant_id', $tenantIds)->exists()
            || MaintenanceRequest::withoutGlobalScopes()->where('photo_path', $path)->whereIn('tenant_id', $tenantIds)->exists()
            || MoveOutRequest::withoutGlobalScopes()->where('photo_path', $path)->whereIn('tenant_id', $tenantIds)->exists()
            || Message::withoutGlobalScopes()
                ->where('attachment_path', $path)
                ->whereHas('thread.participants', fn ($query) => $query->where('users.id', $userId))
                ->exists();
    }
}
