<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\MemberRole;
use App\Enums\Permission;

final class RoleMatrix
{
    /**
     * @return list<string>
     */
    public static function cap(MemberRole $role): array
    {
        $all = array_map(fn (Permission $permission) => $permission->value, Permission::cases());

        return match ($role) {
            MemberRole::Owner => $all,
            MemberRole::Manager => array_values(array_filter(
                $all,
                fn (string $permission) => $permission !== Permission::SettingsManage->value,
            )),
            MemberRole::Collector => [
                Permission::TenantsView->value,
                Permission::InvoicesView->value,
                Permission::PaymentsView->value,
                Permission::CollectionsRecord->value,
                Permission::CollectionsRemit->value,
                Permission::MessagesUse->value,
            ],
            MemberRole::Accountant => [
                Permission::InvoicesView->value,
                Permission::InvoicesManage->value,
                Permission::PaymentsView->value,
                Permission::PaymentsValidate->value,
                Permission::ExpensesView->value,
                Permission::ExpensesManage->value,
                Permission::ReportsView->value,
                Permission::ReportsFinancial->value,
                Permission::RemittancesConfirm->value,
                Permission::MessagesUse->value,
            ],
            MemberRole::Technician => [
                Permission::MaintenanceManage->value,
                Permission::MessagesUse->value,
            ],
        };
    }

    /**
     * @return list<string>
     */
    public static function defaults(MemberRole $role): array
    {
        return self::cap($role);
    }

    /**
     * @param  list<string>  $requested
     * @return list<string>
     */
    public static function intersect(MemberRole $role, array $requested): array
    {
        return array_values(array_intersect(self::cap($role), $requested));
    }
}
