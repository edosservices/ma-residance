<?php

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Services\ContractService;
use App\Services\HousingService;
use App\Services\MemberService;
use App\Services\RegistrationService;
use App\Services\TenantService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentTraceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_a_tenant_sends_proof_and_both_sides_see_the_same_status(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 09:00:00', 'Africa/Kinshasa'));
        Storage::fake('local');
        [$owner, $jean, $invoice] = $this->lease();

        $this->actingAs($jean)
            ->post(route('portal.payments.declare', $invoice), [
                'amount' => '70.00',
                'method' => 'transfer',
                'note' => 'Reçu mobile',
            ])
            ->assertSessionHasErrors('proof');

        $this->actingAs($jean)
            ->post(route('portal.payments.declare', $invoice), [
                'amount' => '70.00',
                'method' => 'transfer',
                'note' => 'Reçu mobile',
                'proof' => UploadedFile::fake()->image('preuve.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $payment = Payment::withoutGlobalScopes()->firstOrFail();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertNotNull($payment->proof_path);
        Storage::disk('local')->assertExists($payment->proof_path);

        $this->actingAs($jean)->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('À confirmer')
            ->assertSee('Approuvé')
            ->assertSee('Déjà payé')
            ->assertSee('Attendu')
            ->assertSee($payment->reference);

        $this->actingAs($owner)->get(route('office.dashboard'))
            ->assertOk()
            ->assertSee('Paiements à confirmer')
            ->assertSee('Jean')
            ->assertSee('Revenus encaissés');

        $this->actingAs($owner)->post(route('office.payments.approve', $payment))->assertRedirect();
        $payment->refresh();
        $this->assertSame(PaymentStatus::Approved, $payment->status);
        $this->assertNotNull($payment->reviewed_at);

        $this->actingAs($jean)->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Approuvé')
            ->assertSee('Déjà payé');

        $this->actingAs($owner)->get(route('office.payments.show', $payment))
            ->assertOk()
            ->assertSee('Approuvé')
            ->assertSee('compté dans les encaissements');
    }

    public function test_a_landlord_hands_a_collaborator_the_login_he_created(): void
    {
        $owner = app(RegistrationService::class)->registerLandlord('Omar', '0810000001', null, 'password', 'Chez Omar');

        $this->actingAs($owner)
            ->post(route('office.members.store'), [
                'name' => 'Sarah',
                'phone' => '0810000002',
                'password' => 'password',
                'role' => MemberRole::Collector->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('access_slip', function (array $slip) {
                return $slip['name'] === 'Sarah'
                    && $slip['password'] === 'password'
                    && $slip['existing'] === false
                    && str_contains($slip['phone'], '810000002');
            });

        $this->actingAs($owner)->get(route('office.members.index'))
            ->assertOk()
            ->assertSee('Identifiants à remettre en main propre')
            ->assertSee('810000002')
            ->assertSee('Sarah');

        $sarah = app(MemberService::class)->create(
            Organization::query()->firstOrFail(),
            $owner,
            [
                'name' => 'Paul',
                'phone' => '0810000004',
                'password' => 'password',
                'role' => MemberRole::Technician->value,
            ],
        );

        $this->actingAs($sarah->user)->get(route('office.dashboard'))
            ->assertOk()
            ->assertSee('Incidents signalés');

        $collector = \App\Models\User::query()->where('name', 'Sarah')->firstOrFail();
        $this->actingAs($collector)->get(route('office.dashboard'))
            ->assertOk()
            ->assertSee('Recouvrement')
            ->assertSee('Espèces confirmées');

        $known = app(RegistrationService::class)->registerTenant('Amina', '0820000009', null, 'ancien-mot');
        $this->actingAs($owner)
            ->post(route('office.members.store'), [
                'name' => 'Amina',
                'phone' => '0820000009',
                'password' => 'nouveau-mot-de-passe',
                'role' => MemberRole::Accountant->value,
            ])
            ->assertSessionHas('access_slip', function (array $slip) {
                return $slip['existing'] === true && $slip['password'] === null;
            });

        $this->assertTrue(password_verify('ancien-mot', $known->fresh()->password));
    }

    public function test_the_office_payment_form_lists_every_client_with_examples(): void
    {
        [$owner, $jean, $invoice] = $this->lease();
        $organization = Organization::query()->firstOrFail();
        app(TenantService::class)->create($organization, $owner, [
            'name' => 'Amina Kabila',
            'phone' => '0820000009',
        ]);

        $this->actingAs($owner)->get(route('office.payments.create'))
            ->assertOk()
            ->assertSee('Clients')
            ->assertSee('Jean')
            ->assertSee($jean->phone)
            ->assertSee('Amina Kabila')
            ->assertSee('+243820000009')
            ->assertSee('Aucune facture ouverte pour ce client.')
            ->assertSee($invoice->number)
            ->assertSee('Appartement A')
            ->assertSee('Ex. Jean ou 0820000001')
            ->assertSee('Ex. 150.00')
            ->assertSee("Ex. Loyer d'octobre, reçu en espèces au bureau")
            ->assertSee('Ex. photo du reçu ou capture du transfert.')
            ->assertSee('Ex. Espèces');

        $this->actingAs($owner)->get(route('office.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Déclarer un paiement')
            ->assertSee('Ex. 70.00')
            ->assertSee("Ex. Loyer d'octobre, reçu en espèces au bureau")
            ->assertSee('Ex. photo du reçu ou capture du transfert');

        $this->actingAs($jean)->get(route('portal.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Déclarer un paiement')
            ->assertSee('Ex. 70.00')
            ->assertSee('Ex. Transfert du 9 octobre, référence 123456', false)
            ->assertSee('Ex. Espèces');
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\User, 2: Invoice}
     */
    private function lease(): array
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
        app(ContractService::class)->accept($request, $owner, [
            'start_date' => '2026-10-01',
            'rent_minor' => 7000,
            'currency' => 'USD',
        ]);

        return [$owner, $jean, Invoice::withoutGlobalScopes()->firstOrFail()];
    }
}
