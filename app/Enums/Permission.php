<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    case PropertiesManage = 'properties.manage';
    case UnitsManage = 'units.manage';
    case TenantsView = 'tenants.view';
    case TenantsManage = 'tenants.manage';
    case ContractsView = 'contracts.view';
    case ContractsManage = 'contracts.manage';
    case InvoicesView = 'invoices.view';
    case InvoicesManage = 'invoices.manage';
    case PaymentsView = 'payments.view';
    case PaymentsValidate = 'payments.validate';
    case CollectionsRecord = 'collections.record';
    case CollectionsRemit = 'collections.remit';
    case RemittancesConfirm = 'remittances.confirm';
    case ExpensesView = 'expenses.view';
    case ExpensesManage = 'expenses.manage';
    case MaintenanceManage = 'maintenance.manage';
    case ReportsView = 'reports.view';
    case ReportsFinancial = 'reports.financial';
    case MembersManage = 'members.manage';
    case MessagesUse = 'messages.use';
    case NotificationsSend = 'notifications.send';
    case SettingsManage = 'settings.manage';
    case MoveOutManage = 'moveout.manage';

    public function label(): string
    {
        return match ($this) {
            self::PropertiesManage => 'Gérer les propriétés',
            self::UnitsManage => 'Gérer les logements',
            self::TenantsView => 'Voir les locataires',
            self::TenantsManage => 'Gérer les locataires',
            self::ContractsView => 'Voir les contrats',
            self::ContractsManage => 'Gérer les contrats',
            self::InvoicesView => 'Voir les factures',
            self::InvoicesManage => 'Gérer les factures',
            self::PaymentsView => 'Voir les paiements',
            self::PaymentsValidate => 'Valider les paiements',
            self::CollectionsRecord => 'Encaisser en espèces',
            self::CollectionsRemit => 'Remettre les espèces',
            self::RemittancesConfirm => 'Confirmer une remise',
            self::ExpensesView => 'Voir les dépenses',
            self::ExpensesManage => 'Enregistrer les dépenses',
            self::MaintenanceManage => 'Gérer la maintenance',
            self::ReportsView => 'Voir les rapports',
            self::ReportsFinancial => 'Voir les rapports financiers',
            self::MembersManage => 'Gérer les collaborateurs',
            self::MessagesUse => 'Utiliser la messagerie',
            self::NotificationsSend => 'Envoyer des notifications',
            self::SettingsManage => 'Paramètres de l\'organisation',
            self::MoveOutManage => 'Valider les départs',
        };
    }
}
