<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_homepage_presents_the_product(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('La gestion immobilière, avec une nouvelle exigence.')
            ->assertSee('Commencer maintenant')
            ->assertSee('Découvrir la plateforme')
            ->assertSee('Multi-résidences')
            ->assertSee('EDOS SERVICES')
            ->assertSee('Solutions numériques et logiciels de gestion conçus pour les entreprises.')
            ->assertSee('+243 992 749 668')
            ->assertSee('tel:+243992749668', false);
    }

    public function test_a_landlord_dashboard_keeps_financial_labels_and_shows_the_shell(): void
    {
        $owner = app(RegistrationService::class)->registerLandlord('Omar', '0810000001', null, 'password', 'Chez Omar');

        $this->actingAs($owner)
            ->get(route('office.dashboard'))
            ->assertOk()
            ->assertSee('Chez Omar')
            ->assertSee('Revenus encaissés')
            ->assertSee('Revenus attendus')
            ->assertSee('Notifications')
            ->assertSee('Locataires en retard')
            ->assertSee('Déjà remis au bailleur')
            ->assertSee('theme-landlord', false);

        $this->actingAs($owner)->get(route('office.notifications.index'))->assertOk()->assertSee('Notifications');
    }

    public function test_super_admin_can_open_a_landlord_account_without_taking_their_place(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin plateforme',
            'phone' => '+243900000001',
            'email' => 'admin@maresidence.test',
            'password' => 'password',
            'status' => UserStatus::Active,
            'timezone' => 'Africa/Kinshasa',
            'locale' => 'fr',
        ]);
        $admin->forceFill(['is_super_admin' => true])->save();

        $this->actingAs($admin)
            ->post(route('admin.organizations.store'), [
                'name' => 'Amina',
                'phone' => '0810000088',
                'organization_name' => 'Résidence Amina',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull(Organization::query()->where('name', 'Résidence Amina')->first());

        $this->actingAs($admin)
            ->get(route('admin.organizations'))
            ->assertOk()
            ->assertSee('Résidence Amina')
            ->assertSee('Amina')
            ->assertSee('theme-admin', false);

        $landlord = User::query()->where('name', 'Amina')->firstOrFail();
        $this->actingAs($landlord)
            ->get(route('office.dashboard'))
            ->assertOk()
            ->assertSee('Résidence Amina')
            ->assertDontSee('Chez Omar');
    }
}
