<?php

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use App\Services\MemberService;
use App\Services\RegistrationService;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_workspace_logs_out_and_private_pages_are_not_cacheable(): void
    {
        $owner = app(RegistrationService::class)->registerLandlord('Omar', '0810000001', null, 'password', 'Chez Omar');
        $organization = Organization::query()->where('name', 'Chez Omar')->firstOrFail();
        $collector = app(MemberService::class)->create($organization, $owner, [
            'name' => 'Sarah',
            'phone' => '0810000002',
            'password' => 'password',
            'role' => MemberRole::Collector->value,
        ])->user;
        $tenant = app(RegistrationService::class)->registerTenant('Jean', '0820000001', null, 'password');
        app(TenantService::class)->ensureForUser($organization, $tenant);
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

        $spaces = [
            [$owner, route('office.dashboard')],
            [$collector, route('office.dashboard')],
            [$tenant, route('portal.dashboard')],
            [$admin, route('admin.dashboard')],
        ];

        foreach ($spaces as [$user, $private]) {
            $this->actingAs($user);
            $page = $this->get($private);
            $page->assertOk();
            $page->assertSee('method="POST"', false);
            $page->assertSee('action="'.route('logout').'"', false);
            $page->assertSee('name="_token"', false);
            $page->assertSee('Se déconnecter');
            $cache = (string) $page->headers->get('Cache-Control');
            $this->assertStringContainsString('no-store', $cache);
            $this->assertStringContainsString('private', $cache);
            $this->assertStringContainsString('must-revalidate', $cache);
            $page->assertHeader('Pragma', 'no-cache');
            $page->assertHeader('Expires', '0');

            $token = session()->token();

            $logout = $this->post(route('logout'))->assertRedirect(route('home'));
            $logoutCache = (string) $logout->headers->get('Cache-Control');
            $this->assertStringContainsString('no-store', $logoutCache);

            $this->assertGuest();
            $this->assertNotSame($token, session()->token());
            $this->get($private)->assertRedirect(route('login'));
        }
    }
}
