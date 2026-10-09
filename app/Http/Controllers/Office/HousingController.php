<?php

declare(strict_types=1);

namespace App\Http\Controllers\Office;

use App\Enums\UnitStatus;
use App\Enums\UnitType;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Unit;
use App\Services\HousingService;
use App\Services\ReportingService;
use App\Support\CurrentContext;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HousingController extends Controller
{
    public function index(CurrentContext $context)
    {
        $properties = Property::query()->withCount('units')->latest()->paginate(20);

        return view('office.properties.index', [
            'properties' => $properties,
            'currencies' => $context->organization()->currencies(),
        ]);
    }

    public function units()
    {
        $units = Unit::query()->with('property')->orderBy('name')->paginate(24);

        return view('office.units.index', compact('units'));
    }

    public function storeProperty(Request $request, CurrentContext $context, HousingService $housing)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $housing->createProperty($context->organization(), $request->user(), [
            ...$data,
            'photo_path' => store_upload($request->file('photo'), 'properties'),
        ]);

        return back()->with('status', 'Propriété ajoutée.');
    }

    public function showProperty(Property $property, CurrentContext $context)
    {
        $this->authorize('view', $property);
        $property->load(['units' => fn ($query) => $query->latest()]);

        return view('office.properties.show', [
            'property' => $property,
            'currencies' => $context->organization()->currencies(),
            'types' => UnitType::cases(),
        ]);
    }

    public function storeUnit(Request $request, Property $property, CurrentContext $context, HousingService $housing)
    {
        $this->authorize('manage', $property);
        $data = $this->validateUnit($request, $context, true);
        $housing->createUnit($property, $request->user(), $data);

        return back()->with('status', 'Logement ajouté.');
    }

    public function showUnit(Unit $unit, Request $request, ReportingService $reporting)
    {
        $this->authorize('view', $unit);
        $unit->load('property', 'priceHistories');
        $period = in_array($request->query('periode'), ['month', 'prev_month', 'year', 'prev_year', 'all'], true)
            ? $request->query('periode')
            : 'all';

        return view('office.units.show', [
            'unit' => $unit,
            'performance' => $reporting->unitPerformance($unit, $period),
            'period' => $period,
            'types' => UnitType::cases(),
            'statuses' => [UnitStatus::Available, UnitStatus::Maintenance, UnitStatus::Unavailable],
        ]);
    }

    public function updateUnit(Request $request, Unit $unit, CurrentContext $context, HousingService $housing)
    {
        $this->authorize('manage', $unit);
        $data = $this->validateUnit($request, $context, false);
        $housing->updateUnit($unit, $request->user(), $data);

        return back()->with('status', 'Logement mis à jour.');
    }

    public function updatePrice(Request $request, Unit $unit, HousingService $housing)
    {
        $this->authorize('manage', $unit);
        $data = $request->validate([
            'price' => ['required', 'string', 'max:20'],
            'currency' => ['required', 'string', 'size:3'],
        ]);
        $housing->changePrice($unit, $request->user(), Money::toMinor($data['price']), strtoupper($data['currency']));

        return back()->with('status', 'Prix mis à jour. Les contrats en cours ne changent pas.');
    }

    public function updateStatus(Request $request, Unit $unit, HousingService $housing)
    {
        $this->authorize('manage', $unit);
        $data = $request->validate(['status' => ['required', Rule::in(['available', 'maintenance', 'unavailable'])]]);
        $housing->changeStatus($unit, $request->user(), UnitStatus::from($data['status']));

        return back()->with('status', 'Statut mis à jour.');
    }

    private function validateUnit(Request $request, CurrentContext $context, bool $withPrice): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:160'],
            'reference' => ['nullable', 'string', 'max:40'],
            'type' => ['required', Rule::enum(UnitType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:30'],
            'features' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ];

        if ($withPrice) {
            $rules['price'] = ['required', 'string', 'max:20'];
            $rules['currency'] = ['required', Rule::in($context->organization()->currencies())];
        }

        $data = $request->validate($rules);
        $features = collect(explode(',', (string) ($data['features'] ?? '')))->map(fn ($item) => trim($item))->filter()->values()->all();

        return [
            'name' => $data['name'],
            'reference' => $data['reference'] ?? null,
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'bedrooms' => $data['bedrooms'] ?? null,
            'features' => $features ?: null,
            'photo_path' => store_upload($request->file('photo'), 'units'),
            'price_minor' => $withPrice ? Money::toMinor($data['price']) : null,
            'currency' => $data['currency'] ?? null,
        ];
    }
}
