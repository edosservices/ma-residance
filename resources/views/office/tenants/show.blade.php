@extends('layouts.shell')
@section('content')
    <x-page-header :title="$tenant->name" subtitle="Dossier locataire" />
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><x-stat label="Téléphone" :value="$tenant->phone" hint="Contact principal" icon="telephone" tone="green" /></div>
        <div class="col-6 col-md-3"><x-stat label="Email" :value="$tenant->email ?: 'Non renseigné'" hint="Échanges écrits" icon="envelope" tone="info" /></div>
        <div class="col-6 col-md-3"><x-stat label="Occupants" :value="$tenant->occupants" hint="Personnes dans le logement" icon="people" tone="mauve" /></div>
        <div class="col-6 col-md-3"><x-stat label="Statut" :value="$tenant->status === 'active' ? 'Actif' : 'Inactif'" hint="Dossier de l'organisation" icon="person-check" tone="warn" /></div>
    </div>
    @if (allows('tenants.manage') && $tenant->notes)
        <article class="card mb-4">
            <p class="kicker">Détails</p>
            <p class="mb-0">{{ $tenant->notes }}</p>
        </article>
    @endif
    <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-muted">Contrats</h2>
    <div class="mt-2 space-y-2">
        @foreach ($tenant->contracts as $contract)
            @if (allows('contracts.view') || allows('contracts.manage'))
                <a class="card block" href="{{ route('office.contracts.show', $contract) }}">
                    <p class="font-semibold">{{ $contract->reference }} · {{ $contract->unit->name }}</p>
                    <x-badge :tone="$contract->status->tone()">{{ $contract->status->label() }}</x-badge>
                </a>
            @else
                <div class="card">
                    <p class="font-semibold">{{ $contract->unit->name }}</p>
                    <p class="text-sm">{{ money($contract->rent_minor, $contract->currency) }}</p>
                </div>
            @endif
        @endforeach
    </div>
    <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-muted">Factures</h2>
    <div class="mt-2 space-y-2">
        @foreach ($tenant->invoices as $invoice)
            <a class="card block" href="{{ route('office.invoices.show', $invoice) }}">
                <div class="flex justify-between gap-2">
                    <p class="font-semibold">{{ $invoice->type->label() }} · {{ $invoice->period_key }}</p>
                    <x-badge :tone="$invoice->status->tone()">{{ $invoice->status->label() }}</x-badge>
                </div>
                <p class="mt-1">{{ money($invoice->amount_minor, $invoice->currency) }}</p>
            </a>
        @endforeach
    </div>
@endsection
