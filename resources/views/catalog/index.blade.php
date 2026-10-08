@extends('layouts.guest')
@section('content')
    <h1 class="text-3xl font-semibold">Logements libres</h1>
    <form class="mt-4" method="GET">
        <input class="field" name="q" value="{{ request('q') }}" placeholder="Rechercher">
    </form>
    <div class="mt-4 space-y-3">
        @forelse ($units as $unit)
            <article class="card">
                <p class="text-xs text-muted">{{ $unit->property->organization->name }} · {{ $unit->property->name }}</p>
                <div class="mt-1 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $unit->name }}</h2>
                        <p class="text-sm text-muted">{{ $unit->type->label() }} @if($unit->bedrooms) · {{ $unit->bedrooms }} ch. @endif</p>
                    </div>
                    <x-badge tone="good">Disponible</x-badge>
                </div>
                @if ($unit->description)
                    <p class="mt-2 text-sm text-muted">{{ $unit->description }}</p>
                @endif
                <p class="mt-3 text-xl font-semibold">{{ money($unit->price_minor, $unit->currency) }}</p>
                @auth
                    <form method="POST" action="{{ route('catalog.request', $unit) }}" class="mt-3 space-y-2">
                        @csrf
                        <input class="field" name="message" placeholder="Un mot pour le bailleur (facultatif)">
                        <button class="btn btn-primary w-full">Demander ce logement</button>
                    </form>
                @else
                    <a class="btn btn-primary mt-3 w-full" href="{{ route('register.tenant') }}">Créer un compte pour demander</a>
                @endauth
            </article>
        @empty
            <p class="card text-sm text-muted">Aucun logement disponible pour le moment.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $units->links() }}</div>
@endsection
