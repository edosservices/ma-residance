@extends('layouts.shell')
@section('content')
    <p class="kicker mb-2">{{ $contract?->unit?->property?->name }}</p>
    <x-page-header title="Bienvenue dans votre espace locataire" :subtitle="$contract ? (($contract->unit->name ?? 'Logement').' · contrat '.$contract->reference) : 'Pas encore de logement'">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('portal.invoices.index') }}"><i class="bi bi-receipt" aria-hidden="true"></i> Mes factures</a>
            <a class="btn btn-ghost" href="{{ route('portal.maintenance.index') }}"><i class="bi bi-tools" aria-hidden="true"></i> Signaler</a>
        </x-slot:actions>
    </x-page-header>
    @if ($contract)
        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-7">
                <article class="card balance-card h-100">
                    <p class="kicker">Mon solde</p>
                    <p class="balance-value tabular">{{ money($due, $currency) }}</p>
                    <p class="text-muted mb-2">Reste à payer · déjà réglé {{ money($paid, $currency) }}</p>
                    <p class="mb-3">
                        <x-badge :tone="$daysLate > 0 ? 'bad' : 'good'">{{ $daysLate > 0 ? $daysLate.' jour'.($daysLate > 1 ? 's' : '').' de retard' : 'À jour' }}</x-badge>
                    </p>
                    <a class="fw-semibold" href="{{ route('portal.invoices.index') }}">Voir le détail du solde</a>
                </article>
            </div>
            <div class="col-12 col-lg-5">
                <article class="card h-100 d-flex flex-column">
                    <p class="kicker">Mes demandes</p>
                    <h2 class="h4">Maintenance du logement</h2>
                    <p class="text-muted">Signalez un incident. Le suivi reste visible dans votre espace.</p>
                    <div class="mt-auto d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="{{ route('portal.maintenance.index') }}">Faire une demande</a>
                        <a class="btn btn-ghost" href="{{ route('portal.messages.index') }}">Messages</a>
                    </div>
                </article>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-8">
                <img class="portal-photo" src="{{ asset('images/brand/residence-facade.jpg') }}" alt="{{ $contract->unit->property->name ?? 'Résidence' }}">
            </div>
            <div class="col-12 col-lg-4">
                <article class="card h-100">
                    <p class="kicker">Mes contacts utiles</p>
                    <strong class="d-block">{{ $owner?->user?->name ?? $currentOrganization->name }}</strong>
                    <p class="text-muted mb-2">Bailleur</p>
                    @if ($owner?->user?->phone || $currentOrganization->phone)
                        <a class="fw-semibold" href="tel:{{ preg_replace('/\s+/', '', $owner?->user?->phone ?? $currentOrganization->phone) }}">{{ $owner?->user?->phone ?? $currentOrganization->phone }}</a>
                    @endif
                </article>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4"><x-stat label="Loyer" :value="money($contract->rent_minor, $contract->currency)" icon="house" tone="mauve" href="{{ route('portal.contract') }}" /></div>
            <div class="col-6 col-md-4"><x-stat label="Payé" :value="money($paid, $currency)" icon="check-circle" tone="green" /></div>
            <div class="col-6 col-md-4"><x-stat label="Reste à payer" :value="money($due, $currency)" icon="exclamation-circle" tone="warn" href="{{ route('portal.invoices.index') }}" /></div>
        </div>
    @else
        <x-empty title="Pas encore de logement" text="Votre bail apparaîtra ici dès qu'il sera actif." />
    @endif
    <div class="row g-3 mb-3">
        @foreach ($charges as $charge)
            <div class="col-12 col-md-6">
                <article class="card h-100">
                    <div class="d-flex justify-content-between gap-2">
                        <strong>{{ $charge['label'] }}</strong>
                        @if ($charge['status'])
                            <x-badge :tone="$charge['status']->tone()">{{ $charge['status']->label() }}</x-badge>
                        @endif
                    </div>
                    <p class="mb-0 mt-2 tabular">Payé {{ money($charge['paid'], $charge['currency']) }}</p>
                    <p class="mb-0 tabular">Reste {{ money($charge['due'], $charge['currency']) }}</p>
                </article>
            </div>
        @endforeach
    </div>
    @if ($next)
        <p class="text-muted">Prochaine échéance {{ $next->due_on->format('d/m/Y') }}</p>
    @endif
    <h2 class="h6 text-uppercase text-muted">Factures</h2>
    @forelse ($invoices as $invoice)
        <a class="card d-block mb-2" href="{{ route('portal.invoices.show', $invoice) }}">
            <div class="d-flex justify-content-between gap-2">
                <strong>{{ $invoice->type->label() }} · {{ $invoice->period_key }}</strong>
                <x-badge :tone="$invoice->status->tone()">{{ $invoice->status->label() }}</x-badge>
            </div>
            <p class="mb-0 mt-1 tabular">Payé {{ money($invoice->netPaidMinor(), $invoice->currency) }} · reste {{ money($invoice->balanceMinor(), $invoice->currency) }}</p>
        </a>
    @empty
        <x-empty title="Aucune facture" text="Vos loyers et charges apparaîtront ici." />
    @endforelse
@endsection
