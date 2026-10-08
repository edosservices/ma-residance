@extends('layouts.shell')
@section('content')
    <p class="text-sm text-muted">{{ $unit->property->name }} · {{ $unit->reference }}</p>
    <div class="mt-1 flex items-start justify-between gap-2">
        <h1 class="text-2xl font-semibold">{{ $unit->name }}</h1>
        <x-badge :tone="$unit->status->tone()">{{ $unit->status->label() }}</x-badge>
    </div>
    <p class="mt-2 text-sm text-muted">{{ $unit->type->label() }} @if($unit->bedrooms) · {{ $unit->bedrooms }} chambres @endif</p>
    @if ($unit->description)
        <p class="mt-2 text-sm">{{ $unit->description }}</p>
    @endif
    @if ($unit->features)
        <p class="mt-2 text-sm text-muted">{{ implode(' · ', $unit->features) }}</p>
    @endif
    <p class="mt-4 text-3xl font-semibold">{{ money($unit->price_minor, $unit->currency) }}</p>
    @include('partials.period', ['period' => $period])
    <div class="grid grid-cols-2 gap-3">
        <x-stat label="Revenus" :value="money($performance['collected'], $performance['currency'])" />
        <x-stat label="Dépenses" :value="money($performance['expenses'], $performance['currency'])" />
        <x-stat label="Net" :value="money($performance['net'], $performance['currency'])" />
        <x-stat label="Maintenance" :value="$performance['maintenance']" hint="interventions" />
    </div>
    <p class="mt-2 text-sm text-muted">{{ $performance['months'] }} mois occupés sur l'historique des contrats.</p>
    @if (allows('units.manage'))
        <details class="card mt-4">
            <summary class="cursor-pointer font-semibold">Modifier le prix</summary>
            <form method="POST" action="{{ route('office.units.price', $unit) }}" class="mt-3 space-y-2">
                @csrf
                <input class="field" name="price" placeholder="Nouveau prix" required>
                <select class="field" name="currency">
                    <option value="{{ $unit->currency }}">{{ $unit->currency }}</option>
                </select>
                <button class="btn btn-primary w-full">Enregistrer le prix</button>
            </form>
            <p class="mt-2 text-xs text-muted">Le prix affiché ne modifie pas les contrats déjà signés.</p>
        </details>
        <form method="POST" action="{{ route('office.units.status', $unit) }}" class="mt-3 flex gap-2">
            @csrf
            <select class="field" name="status">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected($unit->status === $status)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost">Statut</button>
        </form>
    @endif
    <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-muted">Historique des prix</h2>
    <div class="mt-2 space-y-2">
        @foreach ($unit->priceHistories->sortByDesc('effective_at') as $history)
            <p class="text-sm">{{ $history->effective_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · {{ money($history->price_minor, $history->currency) }}</p>
        @endforeach
    </div>
@endsection
