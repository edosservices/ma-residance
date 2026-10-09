<?php

namespace Tests\Feature;

use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceUrgency;
use App\Enums\MemberRole;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Services\ContractService;
use App\Services\HousingService;
use App\Services\MaintenanceService;
use App\Services\MemberService;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamAndUrgentMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_manager_can_only_create_an_agent_with_fewer_permissions(): void
    {
        $owner = app(RegistrationService::class)->registerLandlord('Omar', '0810000001', null, 'password', 'Chez Omar');
        $organization = Organization::query()->firstOrFail();
        $manager = app(MemberService::class)->create($organization, $owner, [
            'name' => 'Nadia',
            'phone' => '0810000008',
            'password' => 'password',
            'role' => MemberRole::Manager->value,
        ]);

        $this->actingAs($manager->user)
            ->get(route('office.members.index'))
            ->assertOk()
            ->assertSee('Le gérant crée uniquement son agent')
            ->assertSee('Agent de recouvrement')
            ->assertDontSee('Comptable')
            ->assertDontSee('Technicien');

        $this->actingAs($manager->user)
            ->post(route('office.members.store'), [
                'name' => 'Paul',
                'phone' => '0810000004',
                'password' => 'password',
                'role' => MemberRole::Technician->value,
            ])
            ->assertSessionHasErrors('role');

        $this->actingAs($manager->user)
            ->post(route('office.members.store'), [
                'name' => 'Sarah',
                'phone' => '0810000002',
                'password' => 'password',
                'role' => MemberRole::Collector->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('access_slip');

        $agent = OrganizationMember::query()->where('role', MemberRole::Collector)->firstOrFail();
        $this->assertEqualsCanonicalizing([
            'tenants.view',
            'invoices.view',
            'payments.view',
            'collections.record',
            'messages.use',
        ], $agent->permissions);
        $this->assertNotContains('traces.share', $agent->permissions);
        $this->assertNotContains('collections.remit', $agent->permissions);
    }

    public function test_an_urgent_maintenance_stays_in_front_of_the_landlord_and_the_manager(): void
    {
        [$owner, $jean, $unit, $tenant] = $this->home();
        $organization = Organization::query()->firstOrFail();
        $manager = app(MemberService::class)->create($organization, $owner, [
            'name' => 'Nadia',
            'phone' => '0810000008',
            'password' => 'password',
            'role' => MemberRole::Manager->value,
        ]);
        $technician = app(MemberService::class)->create($organization, $owner, [
            'name' => 'Paul',
            'phone' => '0810000004',
            'password' => 'password',
            'role' => MemberRole::Technician->value,
        ]);

        $incident = app(MaintenanceService::class)->report($organization, $unit, $jean, [
            'title' => 'Fuite urgente',
            'description' => 'Eau partout dans la cuisine',
            'urgency' => MaintenanceUrgency::High,
        ], $tenant->id);

        $this->actingAs($owner)->get(route('office.dashboard'))
            ->assertOk()
            ->assertSee('Maintenance urgente')
            ->assertSee('Fuite urgente')
            ->assertSee('Jean')
            ->assertSee("C'est bien géré", false);

        $this->actingAs($manager->user)->get(route('office.dashboard'))
            ->assertOk()
            ->assertSee('Maintenance urgente')
            ->assertSee('Fuite urgente');

        $this->actingAs($technician->user)->get(route('office.dashboard'))
            ->assertOk()
            ->assertDontSee('Maintenance urgente');

        $this->actingAs($jean)->get(route('portal.maintenance.show', $incident))
            ->assertOk()
            ->assertSee('Fuite urgente')
            ->assertSee('Signalé')
            ->assertSee('Le détail des étapes reste chez le bailleur.');

        app(MaintenanceService::class)->advance($incident, $owner, MaintenanceStatus::Received, [
            'note' => 'Pris en charge par Omar',
        ]);

        $this->actingAs($jean)->get(route('portal.maintenance.show', $incident))
            ->assertOk()
            ->assertDontSee('Pris en charge par Omar');

        $organization->settings = array_merge($organization->settings ?? [], ['share_declaration_trace' => true]);
        $organization->save();

        $this->actingAs($jean)->get(route('portal.maintenance.show', $incident))
            ->assertOk()
            ->assertSee('Pris en charge par Omar')
            ->assertSee('Omar');

        $this->actingAs($owner)->post(route('office.maintenance.accept', $incident), [
            'note' => 'Fuite réparée',
        ])->assertRedirect();

        $incident->refresh();
        $this->assertNotNull($incident->handled_at);
        $this->actingAs($owner)->get(route('office.dashboard'))
            ->assertOk()
            ->assertDontSee('Maintenance urgente');
        $this->actingAs($owner)->get(route('office.maintenance.show', $incident))
            ->assertOk()
            ->assertSee('Bien géré')
            ->assertSee('Fuite réparée');
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\User, 2: \App\Models\Unit, 3: \App\Models\Tenant}
     */
    private function home(): array
    {
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
        $request = app(ContractService::class)->requestUnit($unit, $jean);
        $contract = app(ContractService::class)->accept($request, $owner, [
            'start_date' => '2026-10-01',
            'rent_minor' => 7000,
            'currency' => 'USD',
        ]);

        return [$owner, $jean, $unit, $contract->tenant];
    }
}
