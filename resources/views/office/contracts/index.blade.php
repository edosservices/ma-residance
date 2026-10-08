@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Contrats</h1>
    @if (allows('contracts.manage'))
        <details class="card mt-4">
            <summary class="cursor-pointer font-semibold">Nouveau contrat</summary>
            <form method="POST" action="{{ route('office.contracts.store') }}" class="mt-3 space-y-2">
                @csrf
                <select class="field" name="tenant_id" required>
                    <option value="">Locataire</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                    @endforeach
                </select>
                <select class="field" name="unit_id" required>
                    <option value="">Logement libre</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }} · {{ money($unit->price_minor, $unit->currency) }}</option>
                    @endforeach
                </select>
                <input class="field" type="date" name="start_date" value="{{ now()->toDateString() }}" required>
                <input class="field" type="date" name="end_date">
                <input class="field" name="rent" placeholder="Loyer" required>
                <select class="field" name="currency">
                    @foreach ($currencies as $currency)
                        <option>{{ $currency }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary w-full">Créer</button>
            </form>
        </details>
    @endif
    <div class="mt-4 space-y-2">
        @foreach ($contracts as $contract)
            <a class="card block" href="{{ route('office.contracts.show', $contract) }}">
                <div class="flex justify-between gap-2">
                    <p class="font-semibold">{{ $contract->reference }}</p>
                    <x-badge :tone="$contract->status->tone()">{{ $contract->status->label() }}</x-badge>
                </div>
                <p class="text-sm text-muted">{{ $contract->tenant->name }} · {{ $contract->unit->name }}</p>
                <p class="mt-1">{{ money($contract->rent_minor, $contract->currency) }}</p>
            </a>
        @endforeach
    </div>
@endsection
