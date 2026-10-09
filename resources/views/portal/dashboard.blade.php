@extends('layouts.shell')
@section('content')
    @php
        $place = $contract
            ? trim(implode(' · ', array_filter([$contract->unit->property->name ?? null, $contract->unit->name ?? null, $contract->reference])))
            : 'Pas encore de logement';
        $contactName = $owner?->user?->name ?? $currentOrganization->name;
        $contactPhone = $owner?->user?->phone ?? $currentOrganization->phone;
        $balanceLine = $due > 0
            ? ($daysLate > 0 ? 'Pensez à régler votre situation rapidement.' : 'Un montant reste ouvert sur votre compte.')
            : 'Bonne nouvelle, vous êtes à jour sur tous vos paiements.';
    @endphp
    <header class="portal-hello">
        <p class="kicker mb-1">{{ $place }}</p>
        <h1>Bienvenue dans votre espace locataire</h1>
    </header>
    <nav class="portal-jumps d-lg-none" aria-label="Accès en un clic">
        <a href="{{ route('portal.invoices.index') }}">Mon solde</a>
        <a href="{{ route('portal.contract') }}">Mes documents</a>
        <a href="{{ route('portal.maintenance.index') }}">Mes demandes</a>
        <a href="#infos">Mes infos utiles</a>
        <a href="{{ route('portal.notifications.index') }}">Actualités</a>
        <a href="{{ route('portal.messages.index') }}">Aide</a>
    </nav>
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
            <article class="card portal-panel h-100">
                <p class="kicker">Mon solde</p>
                <p class="balance-value tabular">{{ money($due, $currency) }}</p>
                <p class="portal-line">{{ $balanceLine }}</p>
                <p class="portal-meta">Déjà réglé {{ money($paid, $currency) }}@if ($next) · prochaine échéance {{ $next->due_on->format('d/m/Y') }}@endif</p>
                <p class="portal-meta">Dernière mise à jour le {{ $asOf->format('d/m/Y à H:i') }}</p>
                <p class="mb-0">
                    <x-badge :tone="$daysLate > 0 ? 'bad' : 'good'">{{ $daysLate > 0 ? $daysLate.' jour'.($daysLate > 1 ? 's' : '').' de retard' : 'À jour' }}</x-badge>
                </p>
                <ul class="portal-trace">
                    <li><span>Déjà payé</span><strong class="tabular">{{ money($paid, $currency) }}</strong></li>
                    <li><span>À confirmer</span><strong class="tabular">{{ money($awaiting, $currency) }}</strong></li>
                    <li><span>Approuvé</span><strong class="tabular">{{ money($approved, $currency) }}</strong></li>
                    <li><span>Attendu</span><strong class="tabular">{{ money($due, $currency) }}</strong></li>
                </ul>
                @foreach ($payments as $payment)
                    <p class="portal-meta">{{ $payment->reference }} · {{ $payment->status->label() }}</p>
                @endforeach
                <div class="portal-actions">
                    <a class="fw-semibold" href="{{ route('portal.invoices.index') }}">Voir le détail du solde</a>
                    @if ($next)
                        <a class="btn btn-primary" href="{{ route('portal.invoices.show', $next) }}">Payer</a>
                    @endif
                </div>
            </article>
        </div>
        <div class="col-12 col-lg-6">
            <article class="card portal-panel h-100">
                <p class="kicker">Mes demandes</p>
                @if ($latestRequest)
                    <h2 class="portal-title">{{ $latestRequest->title }}</h2>
                    <p class="portal-meta">Dernière mise à jour le {{ $latestRequest->updated_at->timezone(config('app.timezone'))->format('d/m/Y à H:i') }}</p>
                    <p class="mb-0"><x-badge :tone="$latestRequest->status->tone()">{{ $latestRequest->status->label() }}</x-badge></p>
                @else
                    <h2 class="portal-title">Aucune demande en cours</h2>
                    <p class="portal-line">Un incident se signale ici. Le suivi reste dans votre espace.</p>
                @endif
                <div class="portal-actions">
                    <a class="btn btn-primary" href="{{ route('portal.maintenance.index') }}">Faire une demande</a>
                    <a class="btn btn-ghost" href="{{ route('portal.maintenance.index') }}">Voir toutes les demandes</a>
                </div>
            </article>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <img class="portal-photo" src="{{ asset('images/brand/residence-facade.jpg') }}" alt="{{ $contract?->unit?->property?->name ?? 'Résidence' }}">
        </div>
        <div class="col-12 col-lg-5">
            <article class="card portal-panel h-100" id="infos">
                <p class="kicker">Mes contacts utiles</p>
                <div class="contact-row">
                    <div>
                        <strong>{{ $contactName }}</strong>
                        <p class="portal-meta mb-0">Bailleur</p>
                    </div>
                    @if ($contactPhone)
                        <a class="contact-call" href="tel:{{ preg_replace('/\s+/', '', $contactPhone) }}" aria-label="Appeler {{ $contactName }}">
                            <i class="bi bi-telephone" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>
                @if ($contactPhone)
                    <p class="portal-meta mb-0">{{ $contactPhone }}</p>
                @endif
                <div class="portal-actions">
                    <a class="btn btn-ghost" href="{{ route('portal.messages.index') }}">Aide</a>
                    <a class="btn btn-ghost" href="{{ route('portal.contract') }}">Mes documents</a>
                    @unless ($contract)
                        <a class="btn btn-primary" href="{{ route('catalog.index', ['parcourir' => 1]) }}">Chercher un logement</a>
                    @endunless
                </div>
            </article>
        </div>
    </div>
@endsection
