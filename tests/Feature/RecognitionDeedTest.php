<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\RecognitionDeed;
use App\Models\Unit;
use App\Services\ContractService;
use App\Services\HousingService;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RecognitionDeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_contract_form_explains_each_field_and_the_guarantee_formula(): void
    {
        [$owner] = $this->home();

        $this->actingAs($owner)
            ->put(route('office.settings.update'), $this->settings(['guarantee_deposit_months' => 2, 'guarantee_advance_months' => 1]))
            ->assertRedirect();

        $this->actingAs($owner)
            ->get(route('office.contracts.index'))
            ->assertOk()
            ->assertSee('Ex. Jean Dupont')
            ->assertSee('Ex. 240.00')
            ->assertSee('photo du contrat signé')
            ->assertSee('2 + 1')
            ->assertSee('Pour changer cette formule, ouvrez les paramètres du bailleur');
    }

    public function test_a_contract_accepts_a_file_and_a_certified_deed_gives_each_side_a_pdf(): void
    {
        [$owner, $jean, $unit] = $this->home();
        $organization = Organization::query()->firstOrFail();
        $free = app(HousingService::class)->createUnit($unit->property, $owner, [
            'name' => 'Chambre 02',
            'reference' => 'CH-02',
            'type' => 'room',
            'price_minor' => 24000,
            'currency' => 'USD',
        ]);
        $tenant = $unit->contracts()->first()->tenant;

        $this->actingAs($owner)
            ->put(route('office.settings.update'), $this->settings([
                'certificate_holder' => 'Omar, bailleur',
                'certificate' => UploadedFile::fake()->image('sceau.jpg'),
            ]))
            ->assertRedirect()
            ->assertSessionHas('status');

        $organization->refresh();
        $this->assertNotSame('', (string) $organization->preference('certificate_code'));

        $this->actingAs($owner)
            ->post(route('office.contracts.store'), [
                'tenant_id' => $tenant->id,
                'unit_id' => $free->id,
                'start_date' => '2026-10-09',
                'rent' => '240.00',
                'currency' => 'USD',
                'conditions' => 'Paiement au plus tard le 5.',
                'attachment' => UploadedFile::fake()->create('contrat.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect();

        $contract = $free->contracts()->firstOrFail();
        $this->assertSame(3, $contract->guarantee_deposit_months);
        $this->assertSame(1, $contract->guarantee_advance_months);
        $this->assertNotNull($contract->attachment_path);

        $this->actingAs($jean)->get(route('portal.deed'))->assertNotFound();

        $this->actingAs($owner)
            ->post(route('office.contracts.deed.store', $contract), [
                'payee_name' => 'Omar, bailleur',
                'deposit_months' => 3,
                'advance_months' => 1,
                'identity_document' => 'carte d\'électeur 123456',
                'origin' => 'Kinshasa, commune de Lemba',
                'premises' => 'Chez Omar · Chambre 02 · Kinshasa',
                'landlord_witnesses' => 'Paul Kabila',
                'tenant_witnesses' => 'André Lumumba',
                'certify' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $deed = RecognitionDeed::query()->firstOrFail();
        $this->assertTrue($deed->isCertified());
        $this->assertSame(24000 * 4, $deed->amount_minor);
        $this->assertSame('Omar, bailleur', $deed->payee_name);

        $landlordPdf = $this->actingAs($owner)->get(route('office.contracts.deed.pdf', $contract));
        $landlordPdf->assertOk();
        $this->assertStringStartsWith('%PDF', $landlordPdf->getContent());
        $this->assertStringContainsString($deed->reference, $landlordPdf->getContent());
        $this->assertStringContainsString($deed->certificate_code, $landlordPdf->getContent());

        $this->actingAs($owner)->get(route('office.contracts.deed', $contract))
            ->assertOk()
            ->assertSee('Copie bailleur')
            ->assertSee('Paul Kabila')
            ->assertSee('Kinshasa, commune de Lemba')
            ->assertSee($deed->certificate_code);

        $tenantPdf = $this->actingAs($jean)->get(route('portal.deed.pdf'));
        $tenantPdf->assertOk();
        $this->assertStringContainsString($deed->certificate_code, $tenantPdf->getContent());

        $this->actingAs($jean)->get(route('portal.contract'))
            ->assertOk()
            ->assertSee('Voir ma copie')
            ->assertSee($deed->reference);

        $this->actingAs($jean)->get(route('portal.deed'))
            ->assertOk()
            ->assertSee('Copie locataire');
    }

    public function test_certification_requires_the_landlord_digital_certificate(): void
    {
        [$owner, $jean, $unit] = $this->home();
        $contract = $unit->contracts()->firstOrFail();

        $this->actingAs($owner)
            ->post(route('office.contracts.deed.store', $contract), [
                'payee_name' => 'Omar',
                'deposit_months' => 3,
                'advance_months' => 1,
                'premises' => 'Chez Omar',
                'certify' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull(RecognitionDeed::query()->first());
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function settings(array $extra = []): array
    {
        return $extra + [
            'generation_day' => 1,
            'due_day' => 30,
            'grace_until_day' => 5,
            'prorata_method' => 'daily',
            'reminder_days_before' => 3,
            'reminder_repeat_days' => 3,
            'default_currency' => 'USD',
            'guarantee_deposit_months' => 3,
            'guarantee_advance_months' => 1,
        ];
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\User, 2: Unit}
     */
    private function home(): array
    {
        $owner = app(RegistrationService::class)->registerLandlord('Omar', '0810000001', null, 'password', 'Chez Omar');
        $organization = Organization::query()->firstOrFail();
        $property = app(HousingService::class)->createProperty($organization, $owner, [
            'name' => 'Chez Omar',
            'address' => 'Avenue de la Gombe',
            'city' => 'Kinshasa',
        ]);
        $unit = app(HousingService::class)->createUnit($property, $owner, [
            'name' => 'Appartement A',
            'reference' => 'APP-A',
            'type' => 'apartment',
            'price_minor' => 24000,
            'currency' => 'USD',
        ]);
        $jean = app(RegistrationService::class)->registerTenant('Jean Dupont', '0820000001', null, 'password');
        $request = app(ContractService::class)->requestUnit($unit, $jean);
        app(ContractService::class)->accept($request, $owner, [
            'start_date' => '2026-10-01',
            'rent_minor' => 24000,
            'currency' => 'USD',
        ]);

        return [$owner, $jean, $unit->fresh()];
    }
}
