<?php

namespace Tests\Feature;

use App\Enums\AllocationMethod;
use App\Enums\CashCollectionStatus;
use App\Enums\ContractStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\MemberRole;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Enums\UnitStatus;
use App\Models\Contract;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\BillingService;
use App\Services\CashCollectionService;
use App\Services\ContractService;
use App\Services\ExchangeRateService;
use App\Services\ExpenseService;
use App\Services\HousingService;
use App\Services\MemberService;
use App\Services\MoveOutService;
use App\Services\PaymentService;
use App\Services\RegistrationService;
use App\Services\RemittanceService;
use App\Services\ReportingService;
use App\Services\TenantService;
use App\Services\UtilityService;
use App\Support\DomainException;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_landlord_can_register_and_open_the_office(): void
    {
        $this->post('/inscription/bailleur', [
            'name' => 'Omar',
            'phone' => '0810000001',
            'password' => 'password',
            'password_confirmation' => 'password',
            'organization_name' => 'Chez Omar',
        ])->assertRedirect(route('office.dashboard'));

        $this->assertDatabaseHas('users', ['phone' => '+243810000001']);
        $this->assertDatabaseHas('organizations', ['name' => 'Chez Omar']);
        $this->get(route('office.dashboard'))->assertOk()->assertSee('Chez Omar');
    }

    public function test_prorata_contract_does_not_follow_later_price_or_rate_changes(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 09:00:00', 'Africa/Kinshasa'));
        [$owner, $organization, $unit, $tenantUser] = $this->rentable();

        $rates = app(ExchangeRateService::class);
        $rates->record($organization, $owner, 'USD', 'CDF', '2900');

        $contract = $this->accept($owner, $organization, $unit, $tenantUser, '2026-10-15', 7000);

        $invoice = Invoice::withoutGlobalScopes()->where('contract_id', $contract->id)->where('type', InvoiceType::Rent)->first();
        $this->assertNotNull($invoice);
        $this->assertSame(3839, (int) $invoice->amount_minor);
        $this->assertSame('2026-10-30', $invoice->due_on->toDateString());
        $this->assertSame(20300000, (int) $contract->equivalent_minor);

        app(HousingService::class)->changePrice($unit, $owner, 9000, 'USD');
        $rates->record($organization, $owner, 'USD', 'CDF', '3100');
        $contract->refresh();

        $this->assertSame(7000, (int) $contract->rent_minor);
        $this->assertSame(0, bccomp((string) $contract->fx_rate, '2900', 8));
        $this->assertSame(3839, (int) $invoice->fresh()->amount_minor);
        $this->assertSame(9000, (int) $unit->fresh()->price_minor);

        $this->expectException(DomainException::class);
        $contract->rent_minor = 100;
        $contract->save();
    }

    public function test_full_and_partial_payments_and_net_exclude_unpaid_invoices(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 09:00:00', 'Africa/Kinshasa'));
        [$owner, $organization, $unit, $tenantUser] = $this->rentable();
        $contract = $this->accept($owner, $organization, $unit, $tenantUser, '2026-10-15', 7000);
        $invoice = Invoice::withoutGlobalScopes()->where('contract_id', $contract->id)->firstOrFail();
        $payments = app(PaymentService::class);

        $partial = $payments->declare($organization, $invoice, $tenantUser, 2000, PaymentMethod::Transfer, 'Acompte');
        $this->assertSame(PaymentStatus::Pending, $partial->status);
        $payments->approve($partial, $owner);
        app(BillingService::class)->applyStatus($invoice->fresh(), CarbonImmutable::parse('2026-10-15', 'Africa/Kinshasa'));
        $this->assertSame(InvoiceStatus::Partial, $invoice->fresh()->status);
        $this->assertSame(1839, app(BillingService::class)->balanceOf($invoice->fresh()));

        $rest = $payments->recordApproved($organization, $invoice->fresh(), $owner, 1839, PaymentMethod::Transfer);
        $this->assertSame(PaymentStatus::Approved, $rest->status);
        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);

        $category = ExpenseCategory::withoutGlobalScopes()->where('organization_id', $organization->id)->where('slug', 'maintenance')->firstOrFail();
        app(ExpenseService::class)->record($organization, $owner, [
            'expense_category_id' => $category->id,
            'property_id' => $unit->property_id,
            'unit_id' => $unit->id,
            'amount_minor' => 1000,
            'currency' => 'USD',
            'spent_on' => '2026-10-15',
            'motif' => 'Réparation douche',
        ]);

        $report = app(ReportingService::class)->financials($organization, null, null);
        $this->assertSame(3839, $report['USD']['expected']);
        $this->assertSame(3839, $report['USD']['collected']);
        $this->assertSame(1000, $report['USD']['expenses']);
        $this->assertSame(2839, $report['USD']['net']);
        $this->assertSame(0, $report['USD']['outstanding']);

        $this->expectException(DomainException::class);
        $rest->delete();
    }

    public function test_cash_collection_needs_both_confirmations_then_remittance(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 09:00:00', 'Africa/Kinshasa'));
        [$owner, $organization, $unit, $tenantUser] = $this->rentable();
        $contract = $this->accept($owner, $organization, $unit, $tenantUser, '2026-10-01', 7000);
        $invoice = Invoice::withoutGlobalScopes()->where('contract_id', $contract->id)->firstOrFail();
        $this->assertSame(7000, (int) $invoice->amount_minor);

        $agent = app(MemberService::class)->create($organization, $owner, [
            'name' => 'Sarah',
            'phone' => '0810000002',
            'password' => 'password',
            'role' => MemberRole::Collector->value,
            'permissions' => ['contracts.manage', 'tenants.view', 'invoices.view', 'payments.view', 'collections.record', 'collections.remit', 'messages.use'],
        ]);
        $this->assertFalse($agent->hasPermission(Permission::ContractsManage));
        $sarah = $agent->user;

        $collections = app(CashCollectionService::class);
        $collection = $collections->initiate($organization, $invoice, $tenantUser, $sarah, 7000, true);
        $this->assertSame(CashCollectionStatus::AwaitingConfirmation, $collection->status);
        $this->assertSame(0, Payment::withoutGlobalScopes()->count());

        $collection = $collections->confirm($collection, $sarah, false);
        $this->assertSame(CashCollectionStatus::Confirmed, $collection->status);
        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertNotNull($collection->payment_id);

        $held = app(ReportingService::class)->held($organization);
        $this->assertSame(7000, $held[0]['amount']);
        $this->assertSame('Sarah', $held[0]['agent']);

        $remittance = app(RemittanceService::class)->submit($organization, $sarah, 'USD');
        $this->assertSame(CashCollectionStatus::RemittancePending, $collection->fresh()->status);

        $this->expectException(DomainException::class);
        app(RemittanceService::class)->confirm($remittance, $sarah);
    }

    public function test_landlord_confirmation_clears_cash_held_by_the_agent(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 09:00:00', 'Africa/Kinshasa'));
        [$owner, $organization, $unit, $tenantUser] = $this->rentable();
        $contract = $this->accept($owner, $organization, $unit, $tenantUser, '2026-10-01', 7000);
        $invoice = Invoice::withoutGlobalScopes()->where('contract_id', $contract->id)->firstOrFail();
        $sarah = app(MemberService::class)->create($organization, $owner, [
            'name' => 'Sarah',
            'phone' => '0810000002',
            'password' => 'password',
            'role' => MemberRole::Collector->value,
        ])->user;

        $collection = app(CashCollectionService::class)->initiate($organization, $invoice, $sarah, $sarah, 7000, false);
        $collection = app(CashCollectionService::class)->confirm($collection, $tenantUser, true);
        $remittance = app(RemittanceService::class)->submit($organization, $sarah, 'USD');
        app(RemittanceService::class)->confirm($remittance, $owner);

        $this->assertSame(CashCollectionStatus::Remitted, $collection->fresh()->status);
        $this->assertSame([], app(ReportingService::class)->held($organization));
        $this->assertDatabaseHas('audit_logs', ['action' => 'remittance.confirmed']);
    }

    public function test_overdue_starts_after_grace_and_reminders_are_not_repeated_the_same_day(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 09:00:00', 'Africa/Kinshasa'));
        [$owner, $organization, $unit, $tenantUser] = $this->rentable();
        $this->accept($owner, $organization, $unit, $tenantUser, '2026-10-15', 7000);
        $invoice = Invoice::withoutGlobalScopes()->firstOrFail();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-27 09:00:00', 'Africa/Kinshasa'));
        $billing = app(BillingService::class);
        $billing->sendReminders();
        $billing->sendReminders();
        $this->assertSame(1, $tenantUser->notifications()->where('data->kind', 'rent.before')->count());

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-05 09:00:00', 'Africa/Kinshasa'));
        $billing->refreshOpenInvoices($organization);
        $this->assertSame(InvoiceStatus::Open, $invoice->fresh()->status);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-06 09:00:00', 'Africa/Kinshasa'));
        $billing->refreshOpenInvoices($organization);
        $this->assertSame(InvoiceStatus::Overdue, $invoice->fresh()->status);
        $this->assertSame(7, $billing->calendar()->daysLate(CarbonImmutable::parse('2026-11-06'), CarbonImmutable::parse($invoice->due_on)));

        $billing->sendReminders();
        $billing->sendReminders();
        $this->assertSame(1, $tenantUser->notifications()->where('data->kind', 'rent.overdue')->count());
    }

    public function test_move_out_is_blocked_while_a_balance_remains_then_releases_the_unit(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 09:00:00', 'Africa/Kinshasa'));
        [$owner, $organization, $unit, $tenantUser] = $this->rentable();
        $contract = $this->accept($owner, $organization, $unit, $tenantUser, '2026-10-01', 7000);
        $invoice = Invoice::withoutGlobalScopes()->where('contract_id', $contract->id)->firstOrFail();

        $moveOut = app(MoveOutService::class)->request($contract, $tenantUser, CarbonImmutable::parse('2026-10-20'), 'Fin de séjour');
        $this->assertSame(UnitStatus::DepartureScheduled, $unit->fresh()->status);
        $this->assertSame(ContractStatus::MoveOutRequested, $contract->fresh()->status);

        try {
            app(MoveOutService::class)->complete($moveOut, $owner, $this->checklist());
            $this->fail('La sortie aurait dû être bloquée.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('solde', $exception->getMessage());
        }

        app(PaymentService::class)->recordApproved($organization, $invoice, $owner, 7000, PaymentMethod::Cash);
        app(MoveOutService::class)->complete($moveOut, $owner, $this->checklist(), 'État correct');

        $this->assertSame(ContractStatus::Ended, $contract->fresh()->status);
        $this->assertSame(UnitStatus::Available, $unit->fresh()->status);
        $this->assertNotNull($contract->fresh()->terminated_at);
    }

    public function test_water_is_split_by_person_and_the_sum_matches(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 09:00:00', 'Africa/Kinshasa'));
        [$owner, $organization, $unit, $tenantUser] = $this->rentable();
        $this->accept($owner, $organization, $unit, $tenantUser, '2026-10-01', 7000);

        $second = app(HousingService::class)->createUnit($unit->property, $owner, [
            'name' => 'Chambre 02',
            'reference' => 'CH-02',
            'type' => 'room',
            'price_minor' => 5000,
            'currency' => 'USD',
        ]);
        $other = app(RegistrationService::class)->registerTenant('Patrick', '0820000002', null, 'password');
        $tenant = app(TenantService::class)->ensureForUser($organization, $other);
        $tenant->occupants = 4;
        $tenant->save();
        $first = Tenant::withoutGlobalScopes()->where('user_id', $tenantUser->id)->firstOrFail();
        $first->occupants = 6;
        $first->save();

        app(ContractService::class)->open(
            $organization,
            $second,
            $tenant,
            $owner,
            CarbonImmutable::parse('2026-10-01'),
            null,
            5000,
            'USD',
        );

        app(UtilityService::class)->allocate(
            $organization,
            $unit->property,
            $owner,
            InvoiceType::Water,
            CarbonImmutable::parse('2026-10-01'),
            10000000,
            'CDF',
            AllocationMethod::PerPerson,
        );

        $shares = Invoice::withoutGlobalScopes()->where('type', InvoiceType::Water)->orderBy('amount_minor')->pluck('amount_minor')->all();
        $this->assertSame([4000000, 6000000], array_map('intval', $shares));
    }

    public function test_reversal_restores_the_balance_without_changing_the_original_payment(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 09:00:00', 'Africa/Kinshasa'));
        [$owner, $organization, $unit, $tenantUser] = $this->rentable();
        $contract = $this->accept($owner, $organization, $unit, $tenantUser, '2026-10-01', 7000);
        $invoice = Invoice::withoutGlobalScopes()->where('contract_id', $contract->id)->firstOrFail();
        $payment = app(PaymentService::class)->recordApproved($organization, $invoice, $owner, 7000, PaymentMethod::Transfer);
        app(PaymentService::class)->reverse($payment, $owner, 'Erreur de saisie');

        $payment->refresh();
        $this->assertSame(7000, (int) $payment->amount_minor);
        $this->assertSame(PaymentStatus::Approved, $payment->status);
        $this->assertSame(7000, app(BillingService::class)->balanceOf($invoice->fresh()));
        $this->assertSame(2, Payment::withoutGlobalScopes()->count());
    }

    /**
     * @return array{0: User, 1: Organization, 2: Unit, 3: User}
     */
    private function rentable(): array
    {
        $owner = app(RegistrationService::class)->registerLandlord('Omar', '0810000001', null, 'password', 'Chez Omar');
        $organization = Organization::query()->firstOrFail();
        $property = app(HousingService::class)->createProperty($organization, $owner, [
            'name' => 'Chez Omar',
            'city' => 'Kinshasa',
            'address' => 'Gombe',
        ]);
        $unit = app(HousingService::class)->createUnit($property, $owner, [
            'name' => 'Appartement A',
            'reference' => 'APP-A',
            'type' => 'apartment',
            'bedrooms' => 2,
            'price_minor' => 25000,
            'currency' => 'USD',
        ]);
        $tenantUser = app(RegistrationService::class)->registerTenant('Jean Dupont', '0820000001', null, 'password');

        return [$owner, $organization, $unit, $tenantUser];
    }

    private function accept(User $owner, Organization $organization, Unit $unit, User $tenantUser, string $start, int $rent): Contract
    {
        $request = app(ContractService::class)->requestUnit($unit, $tenantUser, 'Je souhaite ce logement');

        return app(ContractService::class)->accept($request, $owner, [
            'start_date' => $start,
            'rent_minor' => $rent,
            'currency' => 'USD',
        ]);
    }

    /**
     * @return array<string, bool>
     */
    private function checklist(): array
    {
        return [
            'payments_checked' => true,
            'debts_checked' => true,
            'condition_checked' => true,
            'equipment_checked' => true,
        ];
    }
}
