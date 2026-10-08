@extends('layouts.shell')
@section('content')
    @include('partials.period', ['period' => $period])
    <div class="grid grid-cols-2 gap-3">
        <x-stat label="Propriétés" :value="$stock['properties']" />
        <x-stat label="Logements" :value="$stock['units']" />
        <x-stat label="Occupés" :value="$stock['occupied']" :hint="$stock['occupancy_rate'].' % occupés'" />
        <x-stat label="Libres" :value="$stock['free']" />
        <x-stat label="Maintenance" :value="$stock['maintenance']" />
        <x-stat label="Locataires" :value="$stock['tenants']" />
    </div>
    <div class="mt-4 grid grid-cols-2 gap-2">
        @if (allows('units.manage'))
            <a class="btn btn-primary" href="{{ route('office.properties.index') }}">Ajouter un logement</a>
        @endif
        @if (allows('collections.record') || allows('payments.validate'))
            <a class="btn btn-ghost" href="{{ route('office.payments.create') }}">Encaisser</a>
        @endif
        @if (allows('invoices.view') || allows('payments.view'))
            <a class="btn btn-ghost" href="{{ route('office.invoices.index') }}">Factures</a>
        @endif
        @if (allows('reports.view') || allows('reports.financial'))
            <a class="btn btn-ghost" href="{{ route('office.reports') }}">Performance</a>
        @endif
    </div>
    @foreach ($currencies as $currency => $money)
        <section class="mt-6">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-muted">{{ $currency }} · {{ $period_label }}</h2>
            <div class="grid grid-cols-2 gap-3">
                <x-stat label="Revenus attendus" :value="money($money['expected'], $currency)" hint="Factures dues" />
                <x-stat label="Encaissé" :value="money($money['collected'], $currency)" hint="Paiements validés" />
                <x-stat label="Loyers encaissés" :value="money($money['rent_collected'], $currency)" />
                <x-stat label="Eau" :value="money($money['water_collected'], $currency)" />
                <x-stat label="Électricité" :value="money($money['electricity_collected'], $currency)" />
                <x-stat label="Autres revenus" :value="money($money['other_collected'], $currency)" />
                <x-stat label="Dépenses" :value="money($money['expenses'], $currency)" />
                <x-stat label="Net" :value="money($money['net'], $currency)" hint="Encaissé − dépenses" />
                <x-stat label="À recouvrer" :value="money($money['outstanding'], $currency)" hint="Factures non soldées" />
                <x-stat label="En retard" :value="money($money['overdue'], $currency)" :hint="$money['collection_rate'].' % des factures soldées'" />
            </div>
        </section>
    @endforeach
    @if ($currencies === [])
        <p class="card mt-4 text-sm text-muted">Aucune facture due ni paiement validé sur cette période. Les mois précédents restent dans l'historique.</p>
    @endif
    <section class="mt-6">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-muted">Espèces chez les collaborateurs</h2>
        @forelse ($held as $row)
            <a href="{{ route('office.collections.index') }}" class="card mb-2 block">
                <p class="text-sm text-muted">{{ $row['agent'] }} détient</p>
                <p class="text-xl font-semibold">{{ money($row['amount'], $row['currency']) }}</p>
            </a>
        @empty
            <p class="text-sm text-muted">Aucun agent ne détient d'espèces.</p>
        @endforelse
        <h2 class="mb-3 mt-4 text-sm font-semibold uppercase tracking-wider text-muted">Déjà remis au bailleur</h2>
        @forelse ($remitted as $row)
            <a href="{{ route('office.remittances.index') }}" class="card mb-2 block">
                <p class="text-sm text-muted">{{ $period_label }}</p>
                <p class="text-xl font-semibold">{{ money($row['amount'], $row['currency']) }}</p>
            </a>
        @empty
            <p class="text-sm text-muted">Aucune remise confirmée sur cette période.</p>
        @endforelse
    </section>
    <section class="mt-6 space-y-2">
        <div class="flex items-end justify-between gap-2">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-muted">Locataires en retard</h2>
            <p class="text-xs text-muted">{{ $late_count }} locataire{{ $late_count > 1 ? 's' : '' }}</p>
        </div>
        @foreach ($late_totals as $currency => $amount)
            <p class="text-sm font-semibold">Total en retard {{ money($amount, $currency) }}</p>
        @endforeach
        @forelse ($overdue as $invoice)
            <a href="{{ route('office.invoices.show', $invoice) }}" class="card block">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-semibold">{{ $invoice->tenant->name }}</p>
                    <x-badge tone="bad">{{ $invoice->days_late }} jour{{ $invoice->days_late > 1 ? 's' : '' }}</x-badge>
                </div>
                <p class="text-sm text-muted">{{ $invoice->unit?->reference }} · grâce jusqu'au {{ $invoice->grace_label }}</p>
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
