@extends('layouts.shell')
@section('content')
    <x-page-header title="Locataires" subtitle="Dossiers isolés à votre organisation">
        <x-slot:actions>
            @if (allows('tenants.manage'))
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createTenant"><i class="bi bi-plus-lg" aria-hidden="true"></i> Ajouter un locataire</button>
            @endif
        </x-slot:actions>
    </x-page-header>
    <form class="mt-3" method="GET"><input class="field" name="q" value="{{ request('q') }}" placeholder="Nom ou téléphone"></form>
    @if (allows('tenants.manage'))
        <div class="modal fade" id="createTenant" tabindex="-1" aria-labelledby="createTenantLabel" @if ($errors->any()) data-open-on-load @endif>
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('office.tenants.store') }}" class="modal-content">
                    @csrf
                    <div class="modal-header"><h2 class="modal-title h5" id="createTenantLabel">Ajouter un locataire</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                    <div class="modal-body d-grid gap-3">
                        <label class="small fw-semibold" for="tenant-name">Nom
                            <span class="field-hint">Nom complet, tel qu'il apparaîtra sur le dossier.</span>
                            <input id="tenant-name" class="field mt-1" name="name" value="{{ old('name') }}" placeholder="Ex. Jean Dupont" required>
                        </label>
                        <label class="small fw-semibold" for="tenant-phone">Téléphone
                            <span class="field-hint">Numéro utilisé pour retrouver le locataire et pour sa connexion.</span>
                            <input id="tenant-phone" class="field mt-1" name="phone" value="{{ old('phone') }}" placeholder="Ex. 0820000001" required>
                        </label>
                        <label class="small fw-semibold" for="tenant-email">Email
                            <span class="field-hint">Facultatif. Utile pour les échanges écrits.</span>
                            <input id="tenant-email" class="field mt-1" type="email" name="email" value="{{ old('email') }}" placeholder="Ex. jean@exemple.com">
                        </label>
                        <label class="small fw-semibold" for="tenant-occupants">Personnes dans le logement
                            <span class="field-hint">Nombre d'occupants, locataire compris.</span>
                            <input id="tenant-occupants" class="field mt-1" name="occupants" type="number" min="1" max="30" value="{{ old('occupants', 1) }}" required>
                        </label>
                        <label class="small fw-semibold" for="tenant-notes">Détails
                            <span class="field-hint">Précision de suivi : situation, proche à prévenir, particularité du dossier.</span>
                            <textarea id="tenant-notes" class="field mt-1" name="notes" maxlength="2000" placeholder="Notes internes">{{ old('notes') }}</textarea>
                        </label>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary">Enregistrer</button></div>
                </form>
            </div>
        </div>
    @endif
    <div class="mt-4 space-y-2">
        @foreach ($tenants as $tenant)
            <a class="card block" href="{{ route('office.tenants.show', $tenant) }}">
                <p class="font-semibold mb-1">{{ $tenant->name }}</p>
                <p class="text-sm text-muted mb-1">{{ $tenant->phone }}@if ($tenant->email) · {{ $tenant->email }}@endif</p>
                <p class="text-sm mb-0">{{ $tenant->occupants }} personne{{ $tenant->occupants > 1 ? 's' : '' }} dans le logement</p>
                @if (allows('tenants.manage') && $tenant->notes)
                    <p class="text-sm text-muted mt-1 mb-0">{{ str($tenant->notes)->limit(120) }}</p>
                @endif
            </a>
        @endforeach
    </div>
    <div class="mt-4">{{ $tenants->links() }}</div>
@endsection
