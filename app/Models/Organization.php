<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'name', 'slug', 'phone', 'email', 'status', 'timezone', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'status' => OrganizationStatus::class,
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function preference(string $key, mixed $default = null): mixed
    {
        $defaults = config('residence.organization_defaults', []);
        $settings = $this->settings ?? [];

        return $settings[$key] ?? $defaults[$key] ?? $default;
    }

    /**
     * @return list<string>
     */
    public function currencies(): array
    {
        $currencies = $this->preference('enabled_currencies', ['USD', 'CDF']);

        return array_values(array_filter($currencies, fn ($code) => is_string($code)));
    }
}
