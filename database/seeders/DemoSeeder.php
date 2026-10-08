<?php

namespace Database\Seeders;

use App\Enums\MemberRole;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\ContractService;
use App\Services\ExchangeRateService;
use App\Services\ExpenseService;
use App\Services\HousingService;
use App\Services\MaintenanceService;
use App\Services\MemberService;
use App\Services\RegistrationService;
use App\Services\TenantService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $registration = app(RegistrationService::class);
        $housing = app(HousingService::class);

        $super = User::query()->create([
            'name' => 'Admin plateforme',
            'phone' => '+243900000001',
            'email' => 'admin@maresidence.test',
            'password' => 'password',
            'status' => 'active',
            'timezone' => 'Africa/Kinshasa',
            'locale' => 'fr',
        ]);
        $super->forceFill(['is_super_admin' => true])->save();

        $omar = $registration->registerLandlord('Omar', '0810000001', 'omar@maresidence.test', 'password', 'Chez Omar');
        $organization = $omar->memberships()->first()->organization;

        app(MemberService::class)->create($organization, $omar, [
            'name' => 'Sarah',
            'phone' => '0810000002',
            'password' => 'password',
            'role' => MemberRole::Collector->value,
        ]);

        app(ExchangeRateService::class)->record($organization, $omar, 'USD', 'CDF', '2900');

        $property = $housing->createProperty($organization, $omar, [
            'name' => 'Chez Omar',
            'address' => 'Avenue de la Gombe',
            'city' => 'Kinshasa',
            'description' => 'Résidence de démonstration.',
        ]);

        $apartment = $housing->createUnit($property, $omar, [
            'name' => 'Appartement A',
            'reference' => 'APP-A',
            'type' => 'apartment',
            'description' => '2 chambres + salon',
            'bedrooms' => 2,
            'price_minor' => 25000,
            'currency' => 'USD',
        ]);
        $housing->createUnit($property, $omar, [
            'name' => 'Chambre 01',
            'reference' => 'CH-01',
            'type' => 'room',
            'price_minor' => 7000,
            'currency' => 'USD',
        ]);
        $housing->createUnit($property, $omar, [
            'name' => 'Studio 01',
            'reference' => 'ST-01',
            'type' => 'studio',
            'price_minor' => 12000,
            'currency' => 'USD',
        ]);

        $jeanUser = $registration->registerTenant('Jean Dupont', '0820000001', null, 'password');
        $jean = app(TenantService::class)->ensureForUser($organization, $jeanUser);
        $jean->occupants = 2;
        $jean->save();

        $start = CarbonImmutable::now('Africa/Kinshasa')->startOfMonth();
        app(ContractService::class)->open(
            $organization,
            $apartment,
            $jean,
            $omar,
            $start,
            null,
            25000,
            'USD',
            'Loyer payable à la date d\'échéance.',
        );

        $category = ExpenseCategory::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'maintenance')
            ->first();
        if ($category) {
            app(ExpenseService::class)->record($organization, $omar, [
                'expense_category_id' => $category->id,
                'property_id' => $property->id,
                'unit_id' => $apartment->id,
                'tenant_id' => $jean->id,
                'amount_minor' => 1500,
                'currency' => 'USD',
                'spent_on' => $start->toDateString(),
                'motif' => 'Réparation douche',
                'payee' => 'XYZ',
            ]);
        }

        app(MaintenanceService::class)->report($organization, $apartment, $jeanUser, [
            'title' => 'Douche en panne',
            'description' => 'La douche ne fonctionne plus.',
            'urgency' => 'high',
        ], $jean->id);
    }
}
