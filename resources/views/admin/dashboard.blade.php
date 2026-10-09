@extends('layouts.shell')
@section('content')
    <x-page-header title="Plateforme" subtitle="Vue Super Admin de Ma Résidence">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('admin.organizations') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i> Configurer un bailleur</a>
        </x-slot:actions>
    </x-page-header>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-3"><x-stat label="Bailleurs" :value="$organizations" icon="buildings" tone="green" href="{{ route('admin.organizations') }}" /></div>
        <div class="col-6 col-md-4 col-xl-3"><x-stat label="Bailleurs actifs" :value="$activeOrganizations" icon="check-circle" tone="green" /></div>
        <div class="col-6 col-md-4 col-xl-3"><x-stat label="Bailleurs inactifs" :value="$suspendedOrganizations" icon="pause-circle" tone="warn" /></div>
        <div class="col-6 col-md-4 col-xl-3"><x-stat label="Locataires" :value="$tenants" icon="people" tone="mauve" /></div>
        <div class="col-6 col-md-4 col-xl-3"><x-stat label="Résidences" :value="$properties" icon="houses" tone="info" /></div>
        <div class="col-6 col-md-4 col-xl-3"><x-stat label="Logements" :value="$units" icon="door-open" tone="mauve" /></div>
        <div class="col-6 col-md-4 col-xl-3"><x-stat label="Comptes" :value="$users" icon="person-lock" href="{{ route('admin.users') }}" /></div>
        <div class="col-6 col-md-4 col-xl-3"><x-stat label="Paiements validés" :value="$approvedPayments" icon="cash-coin" tone="green" /></div>
    </div>
    <div class="row g-4">
        <div class="col-lg-7">
            <h2 class="h6 text-uppercase text-muted">Bailleurs récents</h2>
            <div class="table-responsive">
                <table class="table table-stack align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Bailleur</th>
                            <th>Logements</th>
                            <th>Locataires</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrganizations as $organization)
                            <tr>
                                <td data-label="Bailleur">
                                    <strong>{{ $organization->name }}</strong>
                                    <span class="d-block small text-muted">{{ $organization->phone ?: 'Téléphone non renseigné' }}</span>
                                </td>
                                <td data-label="Logements">{{ $organization->units_count }}</td>
                                <td data-label="Locataires">{{ $organization->tenants_count }}</td>
                                <td data-label="Statut"><x-badge :tone="$organization->status->value === 'active' ? 'good' : 'warn'">{{ $organization->status->label() }}</x-badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Aucun bailleur pour le moment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-5">
            <h2 class="h6 text-uppercase text-muted">Activité récente</h2>
            @forelse ($activity as $log)
                <article class="card mb-2">
                    <p class="small text-muted mb-1">{{ $log->user?->name ?? 'Système' }} · {{ $log->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                    <p class="mb-0">{{ $log->description }}</p>
                </article>
            @empty
                <x-empty title="Aucune activité" text="Les actions des bailleurs apparaîtront ici." />
            @endforelse
        </div>
    </div>
@endsection
