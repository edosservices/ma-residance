@extends('layouts.shell')
@section('content')
    <x-page-header title="Tableau de bord" subtitle="Logements, locataires et loyers · {{ $period_label }}{{ $property_id ? ' · résidence filtrée' : '' }}">
        <x-slot:actions>
            @if (allows('units.manage') || allows('properties.manage'))
                <a class="btn btn-primary" href="{{ route('office.properties.index') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i> Ajouter un logement</a>
            @endif
            @if (allows('collections.record') || allows('payments.validate'))
                <a class="btn btn-ghost" href="{{ route('office.payments.create') }}"><i class="bi bi-cash-coin" aria-hidden="true"></i> Enregistrer un paiement</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="row g-2 align-items-center mb-3">
        <div class="col-12 col-md-6 col-lg-4">
            <label class="small text-muted" for="propriete">Propriété</label>
            <select class="field" id="propriete" name="propriete" onchange="this.form.submit()">
                <option value="">Toutes les résidences</option>
                @foreach ($properties as $property)
                    <option value="{{ $property->id }}" @selected((int) $property_id === (int) $property->id)>{{ $property->name }}</option>
                @endforeach
            </select>
        </div>
        <input type="hidden" name="periode" value="{{ $period }}">
    </form>
    @include('partials.period', ['period' => $period])

    <article class="card mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="text-start">
            <p class="kicker mb-1">Situation des locataires</p>
            <strong>{{ $stock['tenants'] }} locataire{{ $stock['tenants'] > 1 ? 's' : '' }} · {{ $late_count }} en retard</strong>
            <p class="mb-0 text-muted">Qui occupe un logement, et qui doit encore régler.</p>
        </div>
        @if (allows('tenants.view'))
            <a class="btn btn-ghost" href="{{ route('office.tenants.index') }}">Gérer les locataires</a>
        @endif
    </article>

    @php
        $spotlightCurrency = array_key_first($currencies) ?: $chart_currency;
        $spotlightCollected = $currencies[$spotlightCurrency]['collected'] ?? 0;
    @endphp
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <article class="card spotlight-card h-100">
                <p class="kicker">Taux d'occupation</p>
                <p class="spotlight-value tabular">{{ $stock['occupancy_rate'] }}%</p>
                <p class="mb-0">{{ $stock['occupied'] }} occupés · {{ $stock['free'] }} disponibles</p>
            </article>
        </div>
        <div class="col-6 col-xl-3"><x-stat label="Résidences" :value="$stock['properties']" hint="Biens enregistrés" icon="buildings" /></div>
        <div class="col-6 col-xl-3"><x-stat label="Loyers encaissés" :value="money($spotlightCollected, $spotlightCurrency)" hint="Paiements validés" icon="cash-coin" tone="green" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-stat label="Maintenance" :value="$open_maintenance" hint="Interventions ouvertes" icon="tools" tone="warn" :href="allows('maintenance.manage') ? route('office.maintenance.index') : null" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4"><x-stat label="Logements" :value="$stock['units']" icon="door-open" tone="mauve" :href="allows('units.manage') ? route('office.units.index') : null" /></div>
        <div class="col-6 col-md-4"><x-stat label="Occupés" :value="$stock['occupied']" :hint="$stock['occupancy_rate'].' % occupés'" icon="person-check" tone="info" /></div>
        <div class="col-6 col-md-4"><x-stat label="Disponibles" :value="$stock['free']" icon="house" tone="green" /></div>
        <div class="col-6 col-md-4"><x-stat label="Locataires" :value="$stock['tenants']" icon="people" tone="mauve" :href="allows('tenants.view') ? route('office.tenants.index') : null" /></div>
        <div class="col-6 col-md-4"><x-stat label="Maintenance" :value="$open_maintenance" hint="Interventions ouvertes" icon="tools" tone="warn" :href="allows('maintenance.manage') ? route('office.maintenance.index') : null" /></div>
        <div class="col-6 col-md-4"><x-stat label="Résidences" :value="$stock['properties']" icon="buildings" /></div>
    </div>

    @php
        $displayMoney = $currencies !== [] ? $currencies : [$chart_currency => [
            'collected' => 0, 'expected' => 0, 'outstanding' => 0, 'expenses' => 0, 'net' => 0,
            'rent_collected' => 0, 'water_collected' => 0, 'electricity_collected' => 0,
            'overdue' => 0, 'collection_rate' => 0,
        ]];
    @endphp
    @foreach ($displayMoney as $currency => $money)
        <section class="mb-4">
            <h2 class="h6 text-uppercase text-muted fw-bold">{{ $currency }} · {{ $period_label }}</h2>
            <div class="row g-3">
                <div class="col-6 col-lg-4 col-xl"><x-stat label="Revenus encaissés" :value="money($money['collected'], $currency)" hint="Paiements validés" icon="cash-coin" tone="green" :href="allows('payments.view') ? route('office.payments.index') : null" /></div>
                <div class="col-6 col-lg-4 col-xl"><x-stat label="Revenus attendus" :value="money($money['expected'], $currency)" hint="Factures dues" icon="receipt" tone="mauve" :href="route('office.invoices.index')" /></div>
                <div class="col-6 col-lg-4 col-xl"><x-stat label="Impayés" :value="money($money['outstanding'], $currency)" hint="Factures non soldées" icon="exclamation-circle" tone="warn" href="{{ route('office.invoices.index', ['statut' => 'open']) }}" /></div>
                <div class="col-6 col-lg-4 col-xl"><x-stat label="Dépenses" :value="money($money['expenses'], $currency)" icon="wallet2" tone="mauve" :href="allows('expenses.view') ? route('office.expenses.index') : null" /></div>
                <div class="col-6 col-lg-4 col-xl"><x-stat label="Net" :value="money($money['net'], $currency)" hint="Encaissé − dépenses" icon="graph-up" tone="green" /></div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-6 col-md-3"><x-stat label="Loyers encaissés" :value="money($money['rent_collected'], $currency)" /></div>
                <div class="col-6 col-md-3"><x-stat label="Eau" :value="money($money['water_collected'], $currency)" /></div>
                <div class="col-6 col-md-3"><x-stat label="Électricité" :value="money($money['electricity_collected'], $currency)" /></div>
                <div class="col-6 col-md-3"><x-stat label="En retard" :value="money($money['overdue'], $currency)" :hint="$money['collection_rate'].' % des factures soldées'" tone="bad" href="{{ route('office.invoices.index', ['statut' => 'overdue']) }}" /></div>
            </div>
        </section>
    @endforeach
    @if ($currencies === [])
        <x-empty class="mb-4" icon="graph-up" title="Aucun mouvement sur cette période" text="Aucune facture due ni paiement validé. Les mois précédents restent dans l'historique." />
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <x-chart :series="$series" :currency="$chart_currency" />
        </div>
        <div class="col-lg-4">
            <div class="chart-card h-100 d-flex flex-column gap-3">
                <strong>Occupation et recouvrement</strong>
                <div class="d-flex align-items-center gap-3">
                    <div class="ring" style="--p: {{ min(100, $stock['occupancy_rate']) }}; --tone: #0f6b43"><strong>{{ $stock['occupancy_rate'] }}%</strong></div>
                    <div>
                        <p class="mb-1 fw-semibold">Taux d'occupation</p>
                        <p class="mb-0 small text-muted">{{ $stock['occupied'] }} occupés · {{ $stock['free'] }} disponibles</p>
                    </div>
                </div>
                @php $rate = (float) (collect($currencies)->first()['collection_rate'] ?? 0); @endphp
                <div class="d-flex align-items-center gap-3">
                    <div class="ring" style="--p: {{ min(100, $rate) }}; --tone: #69468f"><strong>{{ $rate }}%</strong></div>
                    <div>
                        <p class="mb-1 fw-semibold">Recouvrement</p>
                        <p class="mb-0 small text-muted">Part des factures dues déjà soldées</p>
                    </div>
                </div>
                @if (allows('reports.view') || allows('reports.financial'))
                    <a class="btn btn-ghost mt-auto" href="{{ route('office.reports', ['periode' => $period, 'propriete' => $property_id]) }}">Voir les rapports</a>
                @endif
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        @if (allows('invoices.manage'))
            <a class="btn btn-ghost" href="{{ route('office.utilities.create') }}"><i class="bi bi-droplet" aria-hidden="true"></i> Créer une facture d'eau ou d'électricité</a>
        @endif
        @if (allows('maintenance.manage'))
            <a class="btn btn-ghost" href="{{ route('office.maintenance.index') }}"><i class="bi bi-tools" aria-hidden="true"></i> Déclarer une maintenance</a>
        @endif
        <a class="btn btn-ghost" href="{{ route('office.invoices.index', ['statut' => 'overdue']) }}"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> Voir les impayés</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <h2 class="h6 text-uppercase text-muted">Espèces chez les collaborateurs</h2>
            @forelse ($held as $row)
                <a href="{{ route('office.collections.index') }}" class="card d-block mb-2">
                    <p class="text-sm text-muted mb-1">{{ $row['agent'] }} détient</p>
                    <p class="h4 mb-0">{{ money($row['amount'], $row['currency']) }}</p>
                </a>
            @empty
                <p class="text-muted">Aucun agent ne détient d'espèces.</p>
            @endforelse
            <h2 class="h6 text-uppercase text-muted mt-4">Déjà remis au bailleur</h2>
            @forelse ($remitted as $row)
                <a href="{{ route('office.remittances.index') }}" class="card d-block mb-2">
                    <p class="text-sm text-muted mb-1">{{ $period_label }}</p>
                    <p class="h4 mb-0">{{ money($row['amount'], $row['currency']) }}</p>
                </a>
            @empty
                <p class="text-muted">Aucune remise confirmée sur cette période.</p>
            @endforelse
        </div>
        <div class="col-lg-6">
            <div class="d-flex justify-content-between gap-2">
                <h2 class="h6 text-uppercase text-muted">Locataires en retard</h2>
                <p class="small text-muted mb-0">{{ $late_count }} locataire{{ $late_count > 1 ? 's' : '' }}</p>
            </div>
            @foreach ($late_totals as $currency => $amount)
                <p class="fw-semibold">Total en retard {{ money($amount, $currency) }}</p>
            @endforeach
            @forelse ($overdue as $invoice)
                <a href="{{ route('office.invoices.show', $invoice) }}" class="card d-block mb-2">
                    <div class="d-flex justify-content-between gap-2">
                        <strong>{{ $invoice->tenant->name }}</strong>
                        <x-badge tone="bad">{{ $invoice->days_late }} jour{{ $invoice->days_late > 1 ? 's' : '' }}</x-badge>
                    </div>
                    <p class="small text-muted mb-1">{{ $invoice->unit?->reference }} · grâce jusqu'au {{ $invoice->grace_label }}</p>
                    <strong>{{ money($invoice->balanceMinor(), $invoice->currency) }}</strong>
                </a>
            @empty
                <p class="text-muted">Aucun retard.</p>
            @endforelse
            <h2 class="h6 text-uppercase text-muted mt-4">Maintenance</h2>
            @forelse ($maintenance as $item)
                <a href="{{ route('office.maintenance.show', $item) }}" class="card d-block mb-2">
                    <div class="d-flex justify-content-between gap-2">
                        <strong>{{ $item->title }}</strong>
                        <x-badge :tone="$item->status->tone()">{{ $item->status->label() }}</x-badge>
                    </div>
                    <p class="small text-muted mb-0">{{ $item->unit->name }}</p>
                </a>
            @empty
                <p class="text-muted">Aucune intervention ouverte.</p>
            @endforelse
        </div>
    </div>
@endsection
