@extends('layouts.shell')
@section('content')
    @include('partials.period', ['period' => $period])
    <div class="grid grid-cols-2 gap-3">
        <x-stat label="Propriétés" :value="$stock['properties']" />
        <x-stat label="Logements" :value="$stock['units']" />
        <x-stat label="Occupés" :value="$stock['occupied']" />
        <x-stat label="Libres" :value="$stock['free']" :hint="$stock['occupancy_rate'].' % occupés'" />
        <x-stat label="Locataires" :value="$stock['tenants']" />
    </div>
    <div class="mt-4 grid grid-cols-2 gap-2">
        @if (allows('units.manage'))
            <a class="btn btn-primary" href="{{ route('office.properties.index') }}">Ajouter un logement</a>
        @endif
        @if (allows('collections.record'))
            <a class="btn btn-ghost" href="{{ route('office.collections.index') }}">Encaisser</a>
        @endif
        @if (allows('invoices.view') || allows('payments.view'))
            <a class="btn btn-ghost" href="{{ route('office.invoices.index') }}">Factures</a>
        @endif
        @if (allows('tenants.view'))
            <a class="btn btn-ghost" href="{{ route('office.tenants.index') }}">Locataires</a>
        @endif
    </div>
    @foreach ($currencies as $currency => $money)
        <section class="mt-6">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-muted">{{ $currency }}</h2>
            <div class="grid grid-cols-2 gap-3">
                <x-stat label="Loyers attendus" :value="money($money['rent_expected'], $currency)" />
                <x-stat label="Loyers encaissés" :value="money($money['rent_collected'], $currency)" />
                <x-stat label="Eau" :value="money($money['water_collected'], $currency)" />
                <x-stat label="Électricité" :value="money($money['electricity_collected'], $currency)" />
                <x-stat label="Autres revenus" :value="money($money['other_collected'], $currency)" />
                <x-stat label="Total encaissé" :value="money($money['collected'], $currency)" />
                <x-stat label="Dépenses" :value="money($money['expenses'], $currency)" />
                <x-stat label="Net" :value="money($money['net'], $currency)" />
                <x-stat label="À recouvrer" :value="money($money['outstanding'], $currency)" />
                <x-stat label="En retard" :value="money($money['overdue'], $currency)" :hint="$money['collection_rate'].' % recouvré'" />
            </div>
        </section>
    @endforeach
    @if ($currencies === [])
        <p class="card mt-4 text-sm text-muted">Aucune facture sur cette période. Le net partira des paiements validés, pas des factures.</p>
    @endif
    <section class="mt-6">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-muted">Chez les collaborateurs</h2>
        @forelse ($held as $row)
            <a href="{{ route('office.collections.index') }}" class="card mb-2 block">
                <p class="text-sm text-muted">{{ $row['agent'] }}</p>
                <p class="text-xl font-semibold">{{ money($row['amount'], $row['currency']) }}</p>
                <p class="text-xs text-muted">Espèces à remettre</p>
            </a>
        @empty
            <p class="text-sm text-muted">Aucun agent ne détient d'espèces.</p>
        @endforelse
    </section>
    <section class="mt-6 space-y-2">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-muted">Retards</h2>
        @forelse ($overdue as $invoice)
            <a href="{{ route('office.invoices.show', $invoice) }}" class="card block">
                <div class="flex items-center justify-between">
                    <p class="font-semibold">{{ $invoice->tenant->name }}</p>
                    <x-badge tone="bad">En retard</x-badge>
                </div>
                <p class="text-sm text-muted">{{ $invoice->unit?->reference }} · {{ $invoice->number }}</p>
                <p class="mt-1 font-semibold">{{ money($invoice->balanceMinor(), $invoice->currency) }}</p>
            </a>
        @empty
            <p class="text-sm text-muted">Aucun retard.</p>
        @endforelse
    </section>
    <section class="mt-6 space-y-2">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-muted">Interventions</h2>
        @forelse ($maintenance as $item)
            <a href="{{ route('office.maintenance.show', $item) }}" class="card block">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-semibold">{{ $item->title }}</p>
                    <x-badge :tone="$item->status->tone()">{{ $item->status->label() }}</x-badge>
                </div>
                <p class="text-sm text-muted">{{ $item->unit->name }}</p>
            </a>
        @empty
            <p class="text-sm text-muted">Aucune intervention ouverte.</p>
        @endforelse
    </section>
@endsection
