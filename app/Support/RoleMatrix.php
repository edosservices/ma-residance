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
                Permission::TracesShare->value,
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
                Permission::TracesShare->value,
            ],
            MemberRole::Technician => [
                Permission::MaintenanceManage->value,
                Permission::MessagesUse->value,
                Permission::TracesShare->value,
            ],
        };
    }

    /**
     * @return list<MemberRole>
     */
    public static function creatable(MemberRole $actor): array
    {
        return match ($actor) {
            MemberRole::Owner => [MemberRole::Manager, MemberRole::Collector, MemberRole::Accountant, MemberRole::Technician],
            MemberRole::Manager => [MemberRole::Collector],
            default => [],
        };
    }

    /**
     * Droits que cet acteur peut cocher pour ce rôle.
     *
     * @return list<string>
     */
    public static function grantable(MemberRole $actor, MemberRole $target): array
    {
        if ($actor === MemberRole::Owner) {
            return self::cap($target);
        }

        if ($actor === MemberRole::Manager && $target === MemberRole::Collector) {
            return [
                Permission::TenantsView->value,
                Permission::InvoicesView->value,
                Permission::PaymentsView->value,
                Permission::CollectionsRecord->value,
                Permission::MessagesUse->value,
            ];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    public static function defaults(MemberRole $role): array
    {
        $cap = self::cap($role);

        if (in_array($role, [MemberRole::Collector, MemberRole::Accountant, MemberRole::Technician], true)) {
            return array_values(array_diff($cap, [Permission::TracesShare->value]));
        }

        return $cap;
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
