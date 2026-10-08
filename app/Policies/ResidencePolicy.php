<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\CashCollection;
use App\Models\CashRemittance;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\MessageThread;
use App\Models\MoveOutRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\CurrentContext;
use Illuminate\Database\Eloquent\Model;

class ResidencePolicy
{
    public function view(User $user, Model $model): bool
    {
        if ($this->tenantOwns($model)) {
            return true;
        }

        return $this->memberCan($model, $this->viewPermissions($model));
    }

    public function manage(User $user, Model $model): bool
    {
        return $this->memberCan($model, $this->managePermissions($model));
    }

    private function tenantOwns(Model $model): bool
    {
        $tenant = app(CurrentContext::class)->optionalTenant();

        if ($tenant === null || (int) $model->getAttribute('organization_id') !== (int) $tenant->organization_id) {
            return false;
        }

        if ($model instanceof Invoice || $model instanceof Payment || $model instanceof Contract || $model instanceof MoveOutRequest) {
            return (int) $model->getAttribute('tenant_id') === (int) $tenant->id;
        }

        if ($model instanceof MaintenanceRequest) {
            return (int) $model->tenant_id === (int) $tenant->id;
        }

        if ($model instanceof MessageThread) {
            return $model->participants()->where('users.id', $tenant->user_id)->exists();
        }

        return false;
    }

    /**
     * @param  list<Permission>  $permissions
     */
    private function memberCan(Model $model, array $permissions): bool
    {
        $context = app(CurrentContext::class);
        $member = $context->member();

        if ($member === null || (int) $model->getAttribute('organization_id') !== (int) $context->organizationId()) {
            return false;
        }

        if ($model instanceof MessageThread) {
            $belongs = $model->participants()->where('users.id', $member->user_id)->exists();

            return $belongs && $member->hasPermission(Permission::MessagesUse);
        }

        if ($model instanceof CashCollection && (int) $model->agent_id === (int) $member->user_id && $member->hasPermission(Permission::CollectionsRecord)) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($member->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Permission>
     */
    private function viewPermissions(Model $model): array
    {
        return match ($model::class) {
            Property::class, Unit::class => [Permission::PropertiesManage, Permission::UnitsManage, Permission::TenantsView],
            Tenant::class => [Permission::TenantsView, Permission::TenantsManage],
            Contract::class => [Permission::ContractsView, Permission::ContractsManage],
            Invoice::class => [Permission::InvoicesView, Permission::InvoicesManage, Permission::PaymentsView],
            Payment::class => [Permission::PaymentsView, Permission::PaymentsValidate],
            CashCollection::class, CashRemittance::class => [Permission::CollectionsRecord, Permission::CollectionsRemit, Permission::RemittancesConfirm, Permission::PaymentsView],
            Expense::class => [Permission::ExpensesView, Permission::ExpensesManage],
            MaintenanceRequest::class => [Permission::MaintenanceManage],
            MoveOutRequest::class => [Permission::MoveOutManage, Permission::ContractsManage],
            default => [],
        };
    }

    /**
     * @return list<Permission>
     */
    private function managePermissions(Model $model): array
    {
        return match ($model::class) {
            Property::class => [Permission::PropertiesManage],
            Unit::class => [Permission::UnitsManage],
            Tenant::class => [Permission::TenantsManage],
            Contract::class, MoveOutRequest::class => [Permission::ContractsManage, Permission::MoveOutManage],
            Invoice::class => [Permission::InvoicesManage],
            Payment::class => [Permission::PaymentsValidate],
            CashCollection::class => [Permission::CollectionsRecord],
            CashRemittance::class => [Permission::RemittancesConfirm, Permission::CollectionsRemit],
            Expense::class => [Permission::ExpensesManage],
            MaintenanceRequest::class => [Permission::MaintenanceManage],
            default => [],
        };
    }
}
