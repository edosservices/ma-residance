<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MemberRole;
use App\Enums\OrganizationStatus;
use App\Enums\UserStatus;
use App\Models\ExpenseCategory;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationService
{
    public function registerLandlord(string $name, string $phone, ?string $email, string $password, string $organizationName): User
    {
        return DB::transaction(function () use ($name, $phone, $email, $password, $organizationName) {
            $user = $this->createUser($name, $phone, $email, $password);
            $organization = Organization::query()->create([
                'name' => $organizationName,
                'slug' => $this->uniqueSlug($organizationName),
                'phone' => $user->phone,
                'email' => $user->email,
                'status' => OrganizationStatus::Active,
                'timezone' => config('residence.timezone'),
                'settings' => config('residence.organization_defaults'),
            ]);

            OrganizationMember::query()->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => MemberRole::Owner,
                'permissions' => null,
                'status' => 'active',
            ]);

            foreach (config('residence.expense_categories') as $slug => $label) {
                ExpenseCategory::query()->create([
                    'organization_id' => $organization->id,
                    'slug' => $slug,
                    'name' => $label,
                ]);
            }

            $this->linkTenants($user);

            return $user;
        });
    }

    public function registerTenant(string $name, string $phone, ?string $email, string $password): User
    {
        return DB::transaction(function () use ($name, $phone, $email, $password) {
            $user = $this->createUser($name, $phone, $email, $password);
            $this->linkTenants($user);

            return $user;
        });
    }

    private function createUser(string $name, string $phone, ?string $email, string $password): User
    {
        return User::query()->create([
            'name' => $name,
            'phone' => Phone::normalize($phone),
            'email' => $email ? mb_strtolower(trim($email)) : null,
            'password' => $password,
            'status' => UserStatus::Active,
            'timezone' => config('residence.timezone'),
            'locale' => 'fr',
        ]);
    }

    private function linkTenants(User $user): void
    {
        Tenant::withoutGlobalScopes()
            ->where('phone', $user->phone)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'residence';
        $slug = $base;
        $i = 2;

        while (Organization::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
