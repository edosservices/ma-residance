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
                    <div class="modal-body d-grid gap-2">
                        <input class="field" name="name" placeholder="Nom" required>
                        <input class="field" name="phone" placeholder="Téléphone" required>
                        <input class="field" name="email" placeholder="Email facultatif">
                        <input class="field" name="occupants" type="number" min="1" value="1" placeholder="Personnes">
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary">Enregistrer</button></div>
                </form>
            </div>
        </div>
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
