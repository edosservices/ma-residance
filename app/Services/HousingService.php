<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UnitStatus;
use App\Enums\UnitType;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Unit;
use App\Models\UnitPriceHistory;
use App\Models\User;
use App\Support\DomainException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class HousingService
{
    public function __construct(private AuditLogger $audit) {}

    public function createProperty(Organization $organization, User $actor, array $data): Property
    {
        $property = Property::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'description' => $data['description'] ?? null,
            'photo_path' => $data['photo_path'] ?? null,
            'status' => 'active',
        ]);

        $this->audit->log($organization->id, $actor, 'property.created', $property, "A créé la propriété {$property->name}.");

        return $property;
    }

    public function createUnit(Property $property, User $actor, array $data): Unit
    {
        return DB::transaction(function () use ($property, $actor, $data) {
            $reference = $data['reference'] ?: $this->reference($property);

            $unit = Unit::withoutGlobalScopes()->create([
                'organization_id' => $property->organization_id,
                'property_id' => $property->id,
                'name' => $data['name'],
                'reference' => $reference,
                'type' => $data['type'] instanceof UnitType ? $data['type'] : UnitType::from($data['type']),
                'description' => $data['description'] ?? null,
                'bedrooms' => $data['bedrooms'] ?? null,
                'features' => $data['features'] ?? null,
                'price_minor' => $data['price_minor'],
                'currency' => $data['currency'],
                'status' => UnitStatus::Available,
                'photo_path' => $data['photo_path'] ?? null,
            ]);

            $this->rememberPrice($unit, $actor, 'Prix initial');
            $this->audit->log($property->organization_id, $actor, 'unit.created', $unit, "A créé le logement {$unit->reference}.");

            return $unit;
        });
    }

    public function updateUnit(Unit $unit, User $actor, array $data): Unit
    {
        $unit->fill([
            'name' => $data['name'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'bedrooms' => $data['bedrooms'] ?? null,
            'features' => $data['features'] ?? null,
            'photo_path' => $data['photo_path'] ?? $unit->photo_path,
        ]);
        $unit->save();

        $this->audit->log($unit->organization_id, $actor, 'unit.updated', $unit, "A modifié le logement {$unit->reference}.");

        return $unit;
    }

    public function changePrice(Unit $unit, User $actor, int $priceMinor, string $currency): Unit
    {
        return DB::transaction(function () use ($unit, $actor, $priceMinor, $currency) {
            $unit = Unit::withoutGlobalScopes()->lockForUpdate()->findOrFail($unit->id);
            $previous = $unit->price_minor;
            $unit->price_minor = $priceMinor;
            $unit->currency = $currency;
            $unit->save();
            $this->rememberPrice($unit, $actor, 'Changement de prix');

            $this->audit->log(
                $unit->organization_id,
                $actor,
                'unit.price_changed',
                $unit,
                'A modifié le prix de '.$unit->reference.' . Les contrats existants conservent leur loyer.',
                ['from' => $previous, 'to' => $priceMinor, 'currency' => $currency],
            );

            return $unit;
        });
    }

    public function changeStatus(Unit $unit, User $actor, UnitStatus $status): Unit
    {
        if (in_array($unit->status, [UnitStatus::Occupied, UnitStatus::DepartureScheduled, UnitStatus::Reserved, UnitStatus::Pending], true)
            && in_array($status, [UnitStatus::Available, UnitStatus::Unavailable, UnitStatus::Maintenance], true)) {
            throw new DomainException('Le statut de ce logement est lié à un contrat ou à une demande.');
        }

        $unit->status = $status;
        $unit->save();
        $this->audit->log($unit->organization_id, $actor, 'unit.status_changed', $unit, "A passé {$unit->reference} au statut {$status->label()}.");

        return $unit;
    }

    private function rememberPrice(Unit $unit, User $actor, string $note): void
    {
        UnitPriceHistory::query()->create([
            'unit_id' => $unit->id,
            'price_minor' => $unit->price_minor,
            'currency' => $unit->currency,
            'effective_at' => CarbonImmutable::now(),
            'changed_by' => $actor->id,
            'note' => $note,
        ]);
    }

    private function reference(Property $property): string
    {
        $count = Unit::withoutGlobalScopes()->where('property_id', $property->id)->count() + 1;

        return strtoupper(substr((string) preg_replace('/[^A-Za-z0-9]/', '', $property->name), 0, 4)).'-'.str_pad((string) $count, 2, '0', STR_PAD_LEFT);
    }
}
