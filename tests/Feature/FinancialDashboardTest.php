<?php

namespace Tests\Feature;

use App\Enums\AllocationMethod;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use App\Services\BillingService;
use App\Services\ContractService;
use App\Services\HousingService;
use App\Services\PaymentService;
use App\Services\RegistrationService;
use App\Services\ReportingService;
use App\Services\TenantService;
use App\Services\UtilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinancialDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_collected_cash_follows_the_payment_date_not_the_invoice(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 09:00:00', 'Africa/Kinshasa'));
        [$owner, $organization, $unit, $tenant] = $this->rentable();
        $request = app(ContractService::class)->requestUnit($unit, $tenant);
        app(ContractService::class)->accept($request, $owner, [
            'start_date' => '2026-10-01',
            'rent_minor' => 7000,
            'currency' => 'USD',
        ]);
        $invoice = Invoice::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('2026-10-30', $invoice->due_on->toDateString());

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-02 09:00:00', 'Africa/Kinshasa'));
        app(PaymentService::class)->recordApproved($organization, $invoice, $owner, 3000, PaymentMethod::Transfer);
        app(BillingService::class)->generateDue(CarbonImmutable::parse('2026-11-02', 'Africa/Kinshasa'));

        $reporting = app(ReportingService::class);
        $october = $reporting->financials(
            $organization,
            CarbonImmutable::parse('2026-10-01'),
            CarbonImmutable::parse('2026-10-31'),
        );
        $november = $reporting->financials(
            $organization,
            CarbonImmutable::parse('2026-11-01'),
            CarbonImmutable::parse('2026-11-30'),
        );

        $this->assertSame(7000, $october['USD']['expected']);
        $this->assertSame(0, $october['USD']['collected']);
        $this->assertSame(4000, $october['USD']['outstanding']);
        $this->assertSame(7000, $november['USD']['expected']);
        $this->assertSame(3000, $november['USD']['collected']);
        $this->assertSame(3000, $november['USD']['net']);
        $this->assertSame(14000, $reporting->financials($organization, null, null)['USD']['expected']);
        $this->assertSame(3000, $reporting->financials($organization, null, null)['USD']['collected']);
    }

    public function test_generating_invoices_twice_does_not_duplicate_them(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-20 09:00:00', 'Africa/Kinshasa'));
        [$owner, $organization, $unit, $tenant] = $this->rentable();
        app(ContractService::class)->open(
            $organization,
            $unit,
            app(TenantService::class)->ensureForUser($organization, $tenant),
            $owner,
            CarbonImmutable::parse('2026-10-01'),
            null,
            7000,
            'USD',
        );
        $billing = app(BillingService::class);
        $billing->generateDue();
        $billing->generateDue();

        $this->assertSame(1, Invoice::withoutGlobalScopes()->where('type', 'rent')->count());
    }

    public function test_fixed_utility_bills_each_occupied_unit(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 09:00:00', 'Africa/Kinshasa'));
        [$owner, $organization, $unit, $tenant] = $this->rentable();
        app(ContractService::class)->open(
            $organization,
            $unit,
            app(TenantService::class)->ensureForUser($organization, $tenant),
            $owner,
            CarbonImmutable::parse('2026-10-01'),
            null,
            7000,
            'USD',
        );
        $second = app(HousingService::class)->createUnit($unit->property, $owner, [
            'name' => 'Chambre 02',
            'reference' => 'CH-02',
            'type' => 'room',
            'price_minor' => 5000,
            'currency' => 'USD',
        ]);
        $other = app(RegistrationService::class)->registerTenant('Patrick', '0820000002', null, 'password');
        app(ContractService::class)->open(
            $organization,
            $second,
            app(TenantService::class)->ensureForUser($organization, $other),
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
            1000000,
            'CDF',
            AllocationMethod::Fixed,
        );

        $amounts = Invoice::withoutGlobalScopes()->where('type', 'water')->pluck('amount_minor')->all();
        $this->assertSame([1000000, 1000000], array_map('intval', $amounts));
    }

    public function test_payment_proofs_stay_private_to_the_organization(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 09:00:00', 'Africa/Kinshasa'));
        [$owner, $organization, $unit, $tenant] = $this->rentable();
        $request = app(ContractService::class)->requestUnit($unit, $tenant);
        $contract = app(ContractService::class)->accept($request, $owner, [
            'start_date' => '2026-10-01',
            'rent_minor' => 7000,
            'currency' => 'USD',
        ]);
        $invoice = Invoice::withoutGlobalScopes()->where('contract_id', $contract->id)->firstOrFail();
        $payment = app(PaymentService::class)->declare($organization, $invoice, $tenant, 1000, PaymentMethod::Transfer);
        Storage::fake('local');
        Storage::disk('local')->put('proofs/secret.pdf', 'preuve');
        $payment->proof_path = 'proofs/secret.pdf';
        $payment->save();

        $other = app(RegistrationService::class)->registerLandlord('Amina', '0810000099', null, 'password', 'Chez Amina');

        $this->actingAs($other)->get(route('files.show', ['path' => 'proofs/secret.pdf']))->assertForbidden();
        $this->actingAs($owner)->get(route('files.show', ['path' => 'proofs/secret.pdf']))->assertOk();
        $this->actingAs($tenant)->get(route('files.show', ['path' => 'proofs/secret.pdf']))->assertOk();
    }

    public function test_dashboard_shows_maintenance_stock_and_remitted_cash(): void
    {
        $owner = app(RegistrationService::class)->registerLandlord('Omar', '0810000001', null, 'password', 'Chez Omar');

        $this->actingAs($owner)
            ->get(route('office.dashboard'))
            ->assertOk()
            ->assertSee('Maintenance')
            ->assertSee('Déjà remis au bailleur')
            ->assertSee('Locataires en retard');

        $this->actingAs($owner)->get(route('office.reports'))->assertOk()->assertSee('Performance')->assertSee('Depuis le début');
    }

    /**
     * @return array{0: User, 1: Organization, 2: Unit, 3: User}
     */
    private function rentable(): array
    {
        $owner = app(RegistrationService::class)->registerLandlord('Omar', '0810000001', null, 'password', 'Chez Omar');
        $organization = Organization::query()->firstOrFail();
        $property = app(HousingService::class)->createProperty($organization, $owner, ['name' => 'Chez Omar', 'city' => 'Kinshasa']);
        $unit = app(HousingService::class)->createUnit($property, $owner, [
            'name' => 'Appartement A',
            'reference' => 'APP-A',
            'type' => 'apartment',
            'price_minor' => 7000,
            'currency' => 'USD',
        ]);
        $tenant = app(RegistrationService::class)->registerTenant('Jean', '0820000001', null, 'password');

        return [$owner, $organization, $unit, $tenant];
    }
}
