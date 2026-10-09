@extends('layouts.shell')
@section('content')
    <x-page-header title="Résidences" subtitle="Chaque résidence regroupe ses logements">
        <x-slot:actions>
            @if (allows('properties.manage'))
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createProperty"><i class="bi bi-plus-lg" aria-hidden="true"></i> Ajouter une résidence</button>
            @endif
            @if (allows('units.manage'))
                <a class="btn btn-ghost" href="{{ route('office.units.index') }}">Voir les logements</a>
            @endif
        </x-slot:actions>
    </x-page-header>
    <div class="row g-3">
        @forelse ($properties as $property)
            <div class="col-12 col-md-6">
                <a class="card d-block h-100" href="{{ route('office.properties.show', $property) }}">
                    <strong>{{ $property->name }}</strong>
                    <p class="text-muted mb-0">{{ $property->city ?: 'Ville non précisée' }} · {{ $property->units_count }} logements</p>
                </a>
            </div>
        @empty
            <div class="col-12"><x-empty icon="buildings" title="Aucune résidence" text="Ajoutez la première pour pouvoir créer des logements." /></div>
        @endforelse
    </div>
    <div class="mt-3">{{ $properties->links() }}</div>
    @if (allows('properties.manage'))
        <div class="modal fade" id="createProperty" tabindex="-1" aria-labelledby="createPropertyLabel" @if ($errors->any() && old('_form') === 'property') data-open-on-load @endif>
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <form method="POST" action="{{ route('office.properties.store') }}" enctype="multipart/form-data" class="modal-content">
                    @csrf
                    <input type="hidden" name="_form" value="property">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="createPropertyLabel">Nouvelle résidence</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body d-grid gap-2">
                        <input class="field" name="name" value="{{ old('name') }}" placeholder="Nom, ex. Chez Omar" required>
                        <input class="field" name="city" value="{{ old('city') }}" placeholder="Ville">
                        <input class="field" name="address" value="{{ old('address') }}" placeholder="Adresse">
                        <textarea class="field" name="description" placeholder="Description">{{ old('description') }}</textarea>
                        <input class="field" type="file" name="photo" accept="image/*">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Annuler</button>
                        <button class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
