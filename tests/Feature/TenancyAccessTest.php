<?php

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Models\Invoice;
use App\Models\Organization;
use App\Services\ContractService;
use App\Services\HousingService;
use App\Services\MemberService;
use App\Services\RegistrationService;
use App\Services\TenantService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_landlord_cannot_open_another_organization(): void
    {
        $omar = app(RegistrationService::class)->registerLandlord('Omar', '0810000001', null, 'password', 'Chez Omar');
        $sarah = app(RegistrationService::class)->registerLandlord('Sarah', '0810000009', null, 'password', 'Chez Sarah');
        $omarOrg = Organization::query()->where('name', 'Chez Omar')->firstOrFail();
        $property = app(HousingService::class)->createProperty($omarOrg, $omar, ['name' => 'Chez Omar', 'city' => 'Kinshasa']);

        $this->actingAs($sarah)
            ->get(route('office.properties.show', $property))
            ->assertNotFound();

        $this->actingAs($omar)
            ->get(route('office.properties.show', $property))
            ->assertOk()
            ->assertSee('Chez Omar');
    }

    public function test_a_collector_cannot_open_contracts_or_change_rent(): void
    {
        $owner = app(RegistrationService::class)->registerLandlord('Omar', '0810000001', null, 'password', 'Chez Omar');
        $organization = Organization::query()->firstOrFail();
        $member = app(MemberService::class)->create($organization, $owner, [
            'name' => 'Sarah',
            'phone' => '0810000002',
            'password' => 'password',
            'role' => MemberRole::Collector->value,
            'permissions' => ['contracts.manage', 'units.manage', 'tenants.manage', 'tenants.view', 'invoices.view', 'payments.view', 'collections.record', 'collections.remit', 'messages.use'],
        ]);

        $this->actingAs($member->user)->get(route('office.contracts.index'))->assertForbidden();
        $this->actingAs($member->user)->get(route('office.dashboard'))->assertOk()->assertSee('Recouvrement');
        $this->actingAs($member->user)->get(route('office.tenants.index'))->assertOk();
    }

    public function test_a_tenant_cannot_read_another_tenants_invoice(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 09:00:00', 'Africa/Kinshasa'));

        $owner = app(RegistrationService::class)->registerLandlord('Omar', '0810000001', null, 'password', 'Chez Omar');
        $organization = Organization::query()->firstOrFail();
        $property = app(HousingService::class)->createProperty($organization, $owner, ['name' => 'Chez Omar']);
        $unit = app(HousingService::class)->createUnit($property, $owner, [
            'name' => 'Appartement A',
            'reference' => 'APP-A',
            'type' => 'apartment',
            'price_minor' => 7000,
            'currency' => 'USD',
        ]);
        $jean = app(RegistrationService::class)->registerTenant('Jean', '0820000001', null, 'password');
        $marie = app(RegistrationService::class)->registerTenant('Marie', '0820000003', null, 'password');
        $request = app(ContractService::class)->requestUnit($unit, $jean);
        app(ContractService::class)->accept($request, $owner, [
            'start_date' => '2026-10-01',
            'rent_minor' => 7000,
            'currency' => 'USD',
        ]);
        app(TenantService::class)->ensureForUser($organization, $marie);
        $invoice = Invoice::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($marie)->get(route('portal.invoices.show', $invoice))->assertForbidden();
        $this->actingAs($jean)->get(route('portal.invoices.show', $invoice))->assertOk();

        $other = app(RegistrationService::class)->registerLandlord('Amina', '0810000008', null, 'password', 'Chez Amina');
        $this->actingAs($other)->get(route('office.invoices.show', $invoice))->assertNotFound();

        CarbonImmutable::setTestNow();
    }
}
