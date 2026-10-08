@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Locataires</h1>
    <form class="mt-3" method="GET"><input class="field" name="q" value="{{ request('q') }}" placeholder="Nom ou téléphone"></form>
    @if (allows('tenants.manage'))
        <details class="card mt-3">
            <summary class="cursor-pointer font-semibold">Ajouter</summary>
            <form method="POST" action="{{ route('office.tenants.store') }}" class="mt-3 space-y-2">
                @csrf
                <input class="field" name="name" placeholder="Nom" required>
                <input class="field" name="phone" placeholder="Téléphone" required>
                <input class="field" name="email" placeholder="Email facultatif">
                <input class="field" name="occupants" type="number" min="1" value="1" placeholder="Personnes">
                <button class="btn btn-primary w-full">Enregistrer</button>
            </form>
        </details>
    @endif
    <div class="mt-4 space-y-2">
        @foreach ($tenants as $tenant)
            <a class="card block" href="{{ route('office.tenants.show', $tenant) }}">
                <p class="font-semibold">{{ $tenant->name }}</p>
                <p class="text-sm text-muted">{{ $tenant->phone }} · {{ $tenant->occupants }} pers.</p>
            </a>
        @endforeach
    </div>
    <div class="mt-4">{{ $tenants->links() }}</div>
@endsection
