@extends('layouts.public')
@section('content')
    <section class="section-space">
        <div class="container">
            <x-page-header title="Logements libres" subtitle="Demandez un logement. Le bailleur reçoit la demande dans son espace." />
            <form class="row g-2 mb-4" method="GET">
                <div class="col-12 col-md-6">
                    <input class="field" name="q" value="{{ request('q') }}" placeholder="Rechercher un logement ou une résidence" aria-label="Rechercher">
                </div>
                <div class="col-auto"><button class="btn btn-primary">Rechercher</button></div>
            </form>
            <div class="row g-3">
                @forelse ($units as $unit)
                    <div class="col-12 col-md-6">
                        <article class="card h-100">
                            <p class="small text-muted">{{ $unit->property->organization->name }} · {{ $unit->property->name }}</p>
                            <div class="d-flex justify-content-between gap-2">
                                <h2 class="h5 mb-0">{{ $unit->name }}</h2>
                                <x-badge tone="good">Disponible</x-badge>
                            </div>
                            <p class="text-muted">{{ $unit->type->label() }} @if($unit->bedrooms) · {{ $unit->bedrooms }} ch. @endif</p>
                            @if ($unit->description)
                                <p class="text-muted">{{ $unit->description }}</p>
                            @endif
                            <p class="h4">{{ money($unit->price_minor, $unit->currency) }}</p>
                            @auth
                                <form method="POST" action="{{ route('catalog.request', $unit) }}" class="d-grid gap-2">
                                    @csrf
                                    <input class="field" name="message" placeholder="Un mot pour le bailleur (facultatif)">
                                    <button class="btn btn-primary">Demander ce logement</button>
                                </form>
                            @else
                                <a class="btn btn-primary" href="{{ route('register.tenant') }}">Créer un compte pour demander</a>
                            @endauth
                        </article>
                    </div>
                @empty
                    <div class="col-12"><x-empty title="Aucun logement disponible" text="Revenez plus tard ou élargissez la recherche." /></div>
                @endforelse
            </div>
            <div class="mt-4">{{ $units->links() }}</div>
        </div>
    </section>
@endsection
