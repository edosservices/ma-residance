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
                    <p class="kicker">Votre situation</p>
                    <p class="balance-value tabular">{{ money($due, $currency) }}</p>
                    <p class="mb-1">{{ $daysLate > 0 ? 'Vous avez un retard sur votre loyer.' : 'Vous êtes à jour.' }}</p>
                    <p class="text-muted mb-2">Reste à payer · déjà réglé {{ money($paid, $currency) }}</p>
                    @if ($next)
                        <p class="text-muted mb-2">Prochaine échéance {{ $next->due_on->format('d/m/Y') }}</p>
                        <a class="btn btn-primary" href="{{ route('portal.invoices.show', $next) }}">Envoyer la preuve de paiement</a>
                    @endif
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
            <div class="col-12 col-md-4"><x-stat label="Déjà payé" :value="money($snapshot['settled'], $currency)" hint="Factures soldées" icon="check-circle" tone="green" /></div>
            <div class="col-12 col-md-4"><x-stat label="À confirmer" :value="money($snapshot['awaiting'], $currency)" hint="Preuve envoyée, pas encore encaissée" icon="hourglass-split" tone="warn" href="{{ route('portal.invoices.index') }}" /></div>
            <div class="col-12 col-md-4"><x-stat label="Approuvé" :value="money($snapshot['approved'], $currency)" hint="Validé par le bailleur, compté comme encaissé" icon="patch-check" tone="info" /></div>
        </div>
        <div class="mb-3">
            <x-chart :series="$snapshot['series']" :currency="$currency" left="Approuvé" right="Attendu" right-class="bar-due" :show-net="false" />
            <p class="small text-muted mt-2 mb-0">Approuvé suit la date de validation. Attendu suit la date d’échéance. Une preuve encore à confirmer n’entre pas dans Approuvé.</p>
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
    <h2 class="h6 text-uppercase text-muted">Suivi des paiements</h2>
    @forelse ($payments as $payment)
        <article class="card mb-2">
            <div class="d-flex justify-content-between gap-2">
                <strong>{{ $payment->reference }}</strong>
                <x-badge :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-badge>
            </div>
            <p class="mb-0 mt-1 tabular">{{ money($payment->amount_minor, $payment->currency) }} · {{ $payment->invoice?->number }}</p>
            <p class="mb-0 small text-muted">
                @if ($payment->status->value === 'approved' && $payment->reviewed_at)
                    Approuvé le {{ $payment->reviewed_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                @elseif ($payment->status->value === 'pending')
                    Preuve envoyée le {{ $payment->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}, en attente de confirmation
                @else
                    {{ $payment->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                    @if ($payment->rejection_reason) · {{ $payment->rejection_reason }} @endif
                @endif
            </p>
        </article>
    @empty
        <p class="text-muted">Aucun paiement déclaré. Envoyez une preuve depuis la facture à régler.</p>
    @endforelse
    <h2 class="h6 text-uppercase text-muted mt-4">Factures</h2>
    @forelse ($invoices as $invoice)
        <a class="card d-block mb-2" href="{{ route('portal.invoices.show', $invoice) }}">
            <div class="d-flex justify-content-between gap-2">
                <strong>{{ $invoice->type->label() }} · {{ $invoice->period_key }}</strong>
                <x-badge :tone="$invoice->status->value === 'paid' ? 'good' : $invoice->status->tone()">{{ $invoice->status->value === 'paid' ? 'Déjà payé' : $invoice->status->label() }}</x-badge>
            </div>
            <p class="mb-0 mt-1 tabular">Payé {{ money($invoice->netPaidMinor(), $invoice->currency) }} · reste {{ money($invoice->balanceMinor(), $invoice->currency) }}</p>
        </a>
    @empty
        <x-empty title="Aucune facture" text="Vos loyers et charges apparaîtront ici." />
    @endforelse
@endsection
