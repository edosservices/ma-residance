@extends('layouts.guest')
@section('content')
    <p class="text-sm font-semibold uppercase tracking-wider text-brand">Gestion locative</p>
    <h1 class="mt-2 text-4xl font-semibold tracking-tight">Vos logements, vos loyers, en un coup d'œil.</h1>
    <p class="mt-3 text-base leading-relaxed text-muted">Pour les bailleurs, les gérants et les locataires. Factures, encaissements, dépenses et maintenance, sans perdre l'historique.</p>
    <div class="mt-6 grid gap-3">
        <a class="btn btn-primary" href="{{ route('login') }}">Se connecter</a>
        <a class="btn btn-ghost" href="{{ route('register.landlord') }}">Créer mon espace bailleur</a>
        <a class="btn btn-ghost" href="{{ route('register.tenant') }}">Je suis locataire</a>
        <a class="btn btn-ghost" href="{{ route('catalog.index') }}">Voir les logements libres</a>
    </div>
    @if ($units->isNotEmpty())
        <div class="mt-8 space-y-3">
            @foreach ($units as $unit)
                <a href="{{ route('catalog.index') }}" class="card block">
                    <p class="text-xs text-muted">{{ $unit->property->name }} · {{ $unit->property->city }}</p>
                    <p class="mt-1 font-semibold">{{ $unit->name }}</p>
                    <p class="text-sm text-muted">{{ $unit->type->label() }}</p>
                    <p class="mt-2 text-lg font-semibold">{{ money($unit->price_minor, $unit->currency) }}</p>
                </a>
            @endforeach
        </div>
    @endif
@endsection
