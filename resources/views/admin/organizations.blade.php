@extends('layouts.shell')
@section('content')
    <x-page-header title="Bailleurs" subtitle="Comptes et organisations de la plateforme">
        <x-slot:actions>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createLandlord"><i class="bi bi-plus-lg" aria-hidden="true"></i> Créer un bailleur</button>
        </x-slot:actions>
    </x-page-header>
    <div class="table-responsive">
        <table class="table table-stack align-middle">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Téléphone</th>
                    <th>Résidence</th>
                    <th>Logements</th>
                    <th>Locataires</th>
                    <th>Statut</th>
                    <th>Création</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($organizations as $organization)
                    <tr>
                        <td data-label="Nom"><strong>{{ $organization->name }}</strong><span class="d-block small text-muted">{{ $organization->members_count }} accès</span></td>
                        <td data-label="Téléphone">{{ $organization->phone ?: '—' }}</td>
                        <td data-label="Résidence">{{ $organization->properties_count }}</td>
                        <td data-label="Logements">{{ $organization->units_count }}</td>
                        <td data-label="Locataires">{{ $organization->tenants_count }}</td>
                        <td data-label="Statut"><x-badge :tone="$organization->status->value === 'active' ? 'good' : 'warn'">{{ $organization->status->label() }}</x-badge></td>
                        <td data-label="Création">{{ $organization->created_at->timezone(config('app.timezone'))->format('d/m/Y') }}</td>
                        <td data-label="Actions">
                            <form method="POST" action="{{ route('admin.organizations.status', $organization) }}">
                                @csrf
                                <button class="btn btn-ghost btn-sm">{{ $organization->status->value === 'active' ? 'Désactiver' : 'Activer' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">Aucun bailleur inscrit.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $organizations->links() }}

    <div class="modal fade" id="createLandlord" tabindex="-1" aria-labelledby="createLandlordLabel" @if ($errors->any()) data-open-on-load @endif>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('admin.organizations.store') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5" id="createLandlordLabel">Nouveau compte bailleur</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body d-grid gap-2">
                    <input class="field" name="organization_name" value="{{ old('organization_name') }}" placeholder="Nom de la résidence" required>
                    <input class="field" name="name" value="{{ old('name') }}" placeholder="Nom du bailleur" required>
                    <input class="field" name="phone" value="{{ old('phone') }}" placeholder="Téléphone" required>
                    <input class="field" type="email" name="email" value="{{ old('email') }}" placeholder="Email facultatif">
                    <input class="field" type="password" name="password" placeholder="Mot de passe" required>
                    <input class="field" type="password" name="password_confirmation" placeholder="Confirmer le mot de passe" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Annuler</button>
                    <button class="btn btn-primary">Créer le compte</button>
                </div>
            </form>
        </div>
    </div>
@endsection
