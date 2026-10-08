<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\Office\CashController;
use App\Http\Controllers\Office\CommunicationController;
use App\Http\Controllers\Office\ContractController;
use App\Http\Controllers\Office\DashboardController;
use App\Http\Controllers\Office\FinanceController;
use App\Http\Controllers\Office\HousingController;
use App\Http\Controllers\Office\OperationController;
use App\Http\Controllers\Office\PeopleController;
use App\Http\Controllers\Portal\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/logements', [CatalogController::class, 'index'])->name('catalog.index');

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [SessionController::class, 'create'])->name('login');
    Route::post('/connexion', [SessionController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/inscription/bailleur', [SessionController::class, 'createLandlord'])->name('register.landlord');
    Route::post('/inscription/bailleur', [SessionController::class, 'storeLandlord'])->middleware('throttle:10,1');
    Route::get('/inscription/locataire', [SessionController::class, 'createTenant'])->name('register.tenant');
    Route::post('/inscription/locataire', [SessionController::class, 'storeTenant'])->middleware('throttle:10,1');
});

Route::post('/deconnexion', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/fichiers/{path}', [FileController::class, 'show'])->where('path', '.*')->name('files.show');
    Route::post('/logements/{unit}/demander', [CatalogController::class, 'request'])->name('catalog.request');

    Route::prefix('espace')->middleware('org')->name('office.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/plus', [DashboardController::class, 'more'])->name('more');

        Route::middleware('perm:properties.manage|units.manage')->group(function () {
            Route::get('/biens', [HousingController::class, 'index'])->name('properties.index');
            Route::post('/biens', [HousingController::class, 'storeProperty'])->middleware('perm:properties.manage')->name('properties.store');
            Route::get('/biens/{property}', [HousingController::class, 'showProperty'])->name('properties.show');
            Route::post('/biens/{property}/logements', [HousingController::class, 'storeUnit'])->middleware('perm:units.manage')->name('units.store');
            Route::get('/logements/{unit}', [HousingController::class, 'showUnit'])->name('units.show');
            Route::put('/logements/{unit}', [HousingController::class, 'updateUnit'])->middleware('perm:units.manage')->name('units.update');
            Route::post('/logements/{unit}/prix', [HousingController::class, 'updatePrice'])->middleware('perm:units.manage')->name('units.price');
            Route::post('/logements/{unit}/statut', [HousingController::class, 'updateStatus'])->middleware('perm:units.manage')->name('units.status');
        });

        Route::middleware('perm:tenants.view|tenants.manage')->group(function () {
            Route::get('/locataires', [PeopleController::class, 'tenants'])->name('tenants.index');
            Route::post('/locataires', [PeopleController::class, 'storeTenant'])->middleware('perm:tenants.manage')->name('tenants.store');
            Route::get('/locataires/{tenant}', [PeopleController::class, 'showTenant'])->name('tenants.show');
        });

        Route::middleware('perm:members.manage')->group(function () {
            Route::get('/equipe', [PeopleController::class, 'members'])->name('members.index');
            Route::post('/equipe', [PeopleController::class, 'storeMember'])->name('members.store');
            Route::put('/equipe/{member}', [PeopleController::class, 'updateMember'])->name('members.update');
        });

        Route::middleware('perm:contracts.view|contracts.manage')->group(function () {
            Route::get('/demandes', [ContractController::class, 'requests'])->name('requests.index');
            Route::get('/demandes/{rentalRequest}', [ContractController::class, 'showRequest'])->name('requests.show');
            Route::post('/demandes/{rentalRequest}/accepter', [ContractController::class, 'accept'])->middleware('perm:contracts.manage')->name('requests.accept');
            Route::post('/demandes/{rentalRequest}/refuser', [ContractController::class, 'refuse'])->middleware('perm:contracts.manage')->name('requests.refuse');
            Route::get('/contrats', [ContractController::class, 'index'])->name('contracts.index');
            Route::post('/contrats', [ContractController::class, 'store'])->middleware('perm:contracts.manage')->name('contracts.store');
            Route::get('/contrats/{contract}', [ContractController::class, 'show'])->name('contracts.show');
            Route::put('/contrats/{contract}', [ContractController::class, 'update'])->middleware('perm:contracts.manage')->name('contracts.update');
        });

        Route::middleware('perm:invoices.view|invoices.manage|payments.view')->group(function () {
            Route::get('/factures', [FinanceController::class, 'invoices'])->name('invoices.index');
            Route::get('/factures/{invoice}', [FinanceController::class, 'showInvoice'])->name('invoices.show');
            Route::post('/factures/{invoice}/annuler', [FinanceController::class, 'cancelInvoice'])->middleware('perm:invoices.manage')->name('invoices.cancel');
            Route::get('/charges/nouvelle', [FinanceController::class, 'createUtility'])->middleware('perm:invoices.manage')->name('utilities.create');
            Route::post('/charges', [FinanceController::class, 'storeUtility'])->middleware('perm:invoices.manage')->name('utilities.store');
            Route::get('/paiements', [FinanceController::class, 'payments'])->name('payments.index');
            Route::get('/paiements/nouveau', [FinanceController::class, 'createPayment'])->middleware('perm:payments.validate|collections.record')->name('payments.create');
            Route::post('/paiements', [FinanceController::class, 'storePayment'])->middleware('perm:payments.validate|collections.record')->name('payments.store');
            Route::get('/paiements/{payment}', [FinanceController::class, 'showPayment'])->name('payments.show');
            Route::post('/paiements/{payment}/valider', [FinanceController::class, 'approvePayment'])->middleware('perm:payments.validate')->name('payments.approve');
            Route::post('/paiements/{payment}/rejeter', [FinanceController::class, 'rejectPayment'])->middleware('perm:payments.validate')->name('payments.reject');
            Route::post('/paiements/{payment}/corriger', [FinanceController::class, 'reversePayment'])->middleware('perm:payments.validate')->name('payments.reverse');
        });

        Route::middleware('perm:collections.record|collections.remit|remittances.confirm|payments.view')->group(function () {
            Route::get('/encaissements', [CashController::class, 'collections'])->name('collections.index');
            Route::post('/encaissements', [CashController::class, 'storeCollection'])->middleware('perm:collections.record')->name('collections.store');
            Route::get('/encaissements/{collection}', [CashController::class, 'showCollection'])->name('collections.show');
            Route::post('/encaissements/{collection}/confirmer', [CashController::class, 'confirmCollection'])->middleware('perm:collections.record')->name('collections.confirm');
            Route::post('/encaissements/{collection}/annuler', [CashController::class, 'cancelCollection'])->middleware('perm:collections.record')->name('collections.cancel');
            Route::get('/remises', [CashController::class, 'remittances'])->name('remittances.index');
            Route::post('/remises', [CashController::class, 'storeRemittance'])->middleware('perm:collections.remit')->name('remittances.store');
            Route::get('/remises/{remittance}', [CashController::class, 'showRemittance'])->name('remittances.show');
            Route::post('/remises/{remittance}/confirmer', [CashController::class, 'confirmRemittance'])->middleware('perm:remittances.confirm')->name('remittances.confirm');
            Route::post('/remises/{remittance}/rejeter', [CashController::class, 'rejectRemittance'])->middleware('perm:remittances.confirm')->name('remittances.reject');
        });

        Route::middleware('perm:expenses.view|expenses.manage')->group(function () {
            Route::get('/depenses', [OperationController::class, 'expenses'])->name('expenses.index');
            Route::post('/depenses', [OperationController::class, 'storeExpense'])->middleware('perm:expenses.manage')->name('expenses.store');
            Route::post('/depenses/{expense}/annuler', [OperationController::class, 'voidExpense'])->middleware('perm:expenses.manage')->name('expenses.void');
        });

        Route::middleware('perm:maintenance.manage')->group(function () {
            Route::get('/maintenance', [OperationController::class, 'maintenance'])->name('maintenance.index');
            Route::post('/maintenance', [OperationController::class, 'storeMaintenance'])->name('maintenance.store');
            Route::get('/maintenance/{maintenance}', [OperationController::class, 'showMaintenance'])->name('maintenance.show');
            Route::post('/maintenance/{maintenance}/avancer', [OperationController::class, 'advanceMaintenance'])->name('maintenance.advance');
        });

        Route::middleware('perm:moveout.manage|contracts.manage')->group(function () {
            Route::get('/departs/{moveOut}', [OperationController::class, 'showMoveOut'])->name('moveouts.show');
            Route::post('/departs/{moveOut}/terminer', [OperationController::class, 'completeMoveOut'])->name('moveouts.complete');
            Route::post('/departs/{moveOut}/refuser', [OperationController::class, 'rejectMoveOut'])->name('moveouts.reject');
        });

        Route::get('/rapports', [DashboardController::class, 'reports'])->middleware('perm:reports.view|reports.financial')->name('reports');

        Route::middleware('perm:messages.use')->group(function () {
            Route::get('/messages', [CommunicationController::class, 'messages'])->name('messages.index');
            Route::post('/messages', [CommunicationController::class, 'storeMessage'])->name('messages.store');
            Route::get('/messages/{thread}', [CommunicationController::class, 'showThread'])->name('messages.show');
            Route::post('/messages/{thread}', [CommunicationController::class, 'reply'])->name('messages.reply');
        });

        Route::get('/notifications', [CommunicationController::class, 'notifications'])->name('notifications.index');
        Route::post('/notifications/lire', [CommunicationController::class, 'readNotifications'])->name('notifications.read');
        Route::post('/notifications', [CommunicationController::class, 'broadcast'])->middleware('perm:notifications.send')->name('notifications.store');

        Route::middleware('perm:settings.manage')->group(function () {
            Route::get('/parametres', [PeopleController::class, 'settings'])->name('settings.edit');
            Route::put('/parametres', [PeopleController::class, 'updateSettings'])->name('settings.update');
            Route::post('/taux', [PeopleController::class, 'storeRate'])->name('rates.store');
        });

        Route::get('/journal', [DashboardController::class, 'audit'])->middleware('perm:settings.manage|reports.financial')->name('audit');
    });

    Route::prefix('moi')->name('portal.')->group(function () {
        Route::get('/', [PortalController::class, 'dashboard'])->middleware('tenant')->name('dashboard');
        Route::middleware('tenant')->group(function () {
            Route::get('/contrat', [PortalController::class, 'contract'])->name('contract');
            Route::get('/factures', [PortalController::class, 'invoices'])->name('invoices.index');
            Route::get('/factures/{invoice}', [PortalController::class, 'showInvoice'])->name('invoices.show');
            Route::post('/factures/{invoice}/declarer', [PortalController::class, 'declarePayment'])->name('payments.declare');
            Route::post('/factures/{invoice}/especes', [PortalController::class, 'cashPayment'])->name('payments.cash');
            Route::post('/encaissements/{collection}/confirmer', [PortalController::class, 'confirmCash'])->name('collections.confirm');
            Route::get('/maintenance', [PortalController::class, 'maintenance'])->name('maintenance.index');
            Route::post('/maintenance', [PortalController::class, 'storeMaintenance'])->name('maintenance.store');
            Route::get('/maintenance/{maintenance}', [PortalController::class, 'showMaintenance'])->name('maintenance.show');
            Route::post('/maintenance/{maintenance}/commentaire', [PortalController::class, 'commentMaintenance'])->name('maintenance.comment');
            Route::get('/depart', [PortalController::class, 'moveOutForm'])->name('moveout.create');
            Route::post('/depart', [PortalController::class, 'storeMoveOut'])->name('moveout.store');
            Route::get('/messages', [PortalController::class, 'messages'])->name('messages.index');
            Route::post('/messages', [PortalController::class, 'storeMessage'])->name('messages.store');
            Route::get('/messages/{thread}', [PortalController::class, 'showThread'])->name('messages.show');
            Route::post('/messages/{thread}', [PortalController::class, 'reply'])->name('messages.reply');
            Route::get('/notifications', [PortalController::class, 'notifications'])->name('notifications.index');
            Route::post('/notifications/lire', [PortalController::class, 'readNotifications'])->name('notifications.read');
        });
    });

    Route::prefix('admin')->middleware('super')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/organisations', [AdminController::class, 'organizations'])->name('organizations');
        Route::post('/organisations/{organization}/statut', [AdminController::class, 'organizationStatus'])->name('organizations.status');
        Route::get('/utilisateurs', [AdminController::class, 'users'])->name('users');
        Route::post('/utilisateurs/{user}/statut', [AdminController::class, 'userStatus'])->name('users.status');
        Route::get('/journal', [AdminController::class, 'audit'])->name('audit');
        Route::get('/parametres', [AdminController::class, 'settings'])->name('settings');
        Route::put('/parametres', [AdminController::class, 'updateSettings'])->name('settings.update');
    });
});
