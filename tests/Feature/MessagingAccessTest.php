<?php

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Enums\UserStatus;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use App\Services\ContractService;
use App\Services\HousingService;
use App\Services\MemberService;
use App\Services\RegistrationService;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessagingAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tenant_and_landlord_exchange_messages_with_a_stored_attachment(): void
    {
        Storage::fake('local');
        [$owner, $organization, $jean] = $this->household();

        $this->actingAs($jean)
            ->post(route('portal.messages.store'), [
                'body' => 'Le robinet fuit.',
                'attachment' => UploadedFile::fake()->create('fuite.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect();

        $message = Message::withoutGlobalScopes()->firstOrFail();
        $this->assertNotNull($message->attachment_path);
        Storage::disk('local')->assertExists($message->attachment_path);
        $thread = $message->message_thread_id;

        $this->actingAs($owner)
            ->get(route('office.messages.show', $thread))
            ->assertOk()
            ->assertSee('Le robinet fuit.')
            ->assertSee('Pièce jointe');

        $this->actingAs($owner)
            ->post(route('office.messages.reply', $thread), [
                'body' => 'Je passe demain.',
            ])
            ->assertRedirect();

        $this->actingAs($jean)
            ->get(route('portal.messages.show', $thread))
            ->assertOk()
            ->assertSeeInOrder(['Le robinet fuit.', 'Je passe demain.']);

        $this->actingAs($owner)
            ->get(route('office.messages.index'))
            ->assertOk()
            ->assertSee('Je passe demain.');

        $this->actingAs($jean)
            ->get(route('files.show', ['path' => $message->attachment_path]))
            ->assertOk();
        $this->actingAs($owner)
            ->get(route('files.show', ['path' => $message->attachment_path]))
            ->assertOk();
    }

    public function test_messages_stay_inside_the_organization_and_the_participants(): void
    {
        Storage::fake('local');
        [$owner, $organization, $jean] = $this->household();
        $marie = app(RegistrationService::class)->registerTenant('Marie', '0820000003', null, 'password');
        app(TenantService::class)->ensureForUser($organization, $marie);
        $colleague = app(MemberService::class)->create($organization, $owner, [
            'name' => 'Paul',
            'phone' => '0810000004',
            'password' => 'password',
            'role' => MemberRole::Accountant->value,
        ])->user;
        $other = app(RegistrationService::class)->registerLandlord('Sarah', '0810000009', null, 'password', 'Chez Sarah');
        $outsider = app(RegistrationService::class)->registerTenant('Inconnu', '0820000009', null, 'password');
        $admin = User::query()->create([
            'name' => 'Admin plateforme',
            'phone' => '+243900000099',
            'email' => 'admin-messages@maresidence.test',
            'password' => 'password',
            'status' => UserStatus::Active,
            'timezone' => 'Africa/Kinshasa',
            'locale' => 'fr',
        ]);
        $admin->forceFill(['is_super_admin' => true])->save();

        $this->actingAs($jean)->post(route('portal.messages.store'), [
            'body' => 'Message privé de Jean.',
            'attachment' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertRedirect();

        $message = Message::withoutGlobalScopes()->where('body', 'Message privé de Jean.')->firstOrFail();
        $thread = $message->message_thread_id;

        $this->actingAs($marie)->get(route('portal.messages.show', $thread))->assertForbidden();
        $this->actingAs($marie)->post(route('portal.messages.reply', $thread), ['body' => 'Je ne devrais pas.'])->assertForbidden();
        $this->actingAs($marie)->get(route('portal.messages.index'))->assertOk()->assertDontSee('Message privé de Jean.');

        $this->actingAs($colleague)->get(route('office.messages.show', $thread))->assertForbidden();
        $this->actingAs($colleague)->post(route('office.messages.reply', $thread), ['body' => 'Je ne devrais pas.'])->assertForbidden();
        $this->actingAs($colleague)->get(route('files.show', ['path' => $message->attachment_path]))->assertForbidden();

        $this->actingAs($other)->get(route('office.messages.show', $thread))->assertNotFound();
        $this->actingAs($other)->post(route('office.messages.reply', $thread), ['body' => 'Autre organisation.'])->assertNotFound();
        $this->actingAs($other)->get(route('files.show', ['path' => $message->attachment_path]))->assertForbidden();

        $this->actingAs($admin)->get(route('files.show', ['path' => $message->attachment_path]))->assertForbidden();
        $this->actingAs($jean)->get(route('office.messages.show', $thread))->assertForbidden();

        $this->actingAs($owner)
            ->from(route('office.messages.index'))
            ->post(route('office.messages.store'), [
                'recipient_id' => $outsider->id,
                'body' => 'Hors organisation.',
            ])
            ->assertRedirect(route('office.messages.index'))
            ->assertSessionHas('error', 'Ce destinataire n\'appartient pas à votre organisation.');

        $this->assertSame(1, Message::withoutGlobalScopes()->count());
    }

    /**
     * @return array{0: User, 1: Organization, 2: User}
     */
    private function household(): array
    {
        $owner = app(RegistrationService::class)->registerLandlord('Omar', '0810000001', null, 'password', 'Chez Omar');
        $organization = Organization::query()->where('name', 'Chez Omar')->firstOrFail();
        $property = app(HousingService::class)->createProperty($organization, $owner, ['name' => 'Chez Omar', 'city' => 'Kinshasa']);
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

        return [$owner, $organization, $jean];
    }
}
