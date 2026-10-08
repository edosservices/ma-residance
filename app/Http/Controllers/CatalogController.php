<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UnitStatus;
use App\Models\Unit;
use App\Services\ContractService;
use App\Support\HomeRedirect;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function home()
    {
        if (auth()->check()) {
            return HomeRedirect::for(auth()->user());
        }

        $units = Unit::withoutGlobalScopes()
            ->with('property.organization')
            ->where('status', UnitStatus::Available)
            ->latest()
            ->limit(6)
            ->get();

        return view('home', compact('units'));
    }

    public function index(Request $request)
    {
        $units = Unit::withoutGlobalScopes()
            ->with('property.organization')
            ->where('status', UnitStatus::Available)
            ->when($request->string('q')->toString(), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('property', function ($property) use ($search) {
                            $property->where('name', 'like', "%{$search}%")
                                ->orWhere('city', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('catalog.index', compact('units'));
    }

    public function request(Request $request, Unit $unit, ContractService $contracts)
    {
        $data = $request->validate(['message' => ['nullable', 'string', 'max:1000']]);
        $unit = Unit::withoutGlobalScopes()->findOrFail($unit->id);
        $contracts->requestUnit($unit, $request->user(), $data['message'] ?? null);

        return redirect()->route('catalog.index')->with('status', 'Demande envoyée au bailleur.');
    }
}
