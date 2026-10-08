@extends('layouts.shell')
@section('content')
    <x-page-header title="Logements" subtitle="Appartements, chambres et statuts">
        <x-slot:actions>
            @if (allows('properties.manage'))
                <a class="btn btn-primary" href="{{ route('office.properties.index') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i> Ajouter via une résidence</a>
            @endif
        </x-slot:actions>
    </x-page-header>
    <div class="table-responsive">
        <table class="table table-stack align-middle">
            <thead>
                <tr><th>Logement</th><th>Résidence</th><th>Type</th><th>Loyer affiché</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($units as $unit)
                    <tr>
                        <td data-label="Logement"><strong>{{ $unit->name }}</strong><span class="d-block small text-muted">{{ $unit->reference }}</span></td>
                        <td data-label="Résidence">{{ $unit->property->name }}</td>
                        <td data-label="Type">{{ $unit->type->label() }}</td>
                        <td data-label="Loyer">{{ money($unit->price_minor, $unit->currency) }}</td>
                        <td data-label="Statut"><x-badge :tone="$unit->status->tone()">{{ $unit->status->label() }}</x-badge></td>
                        <td data-label=""><a class="btn btn-ghost btn-sm" href="{{ route('office.units.show', $unit) }}">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">Aucun logement. Ajoutez d'abord une résidence.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $units->links() }}
@endsection
