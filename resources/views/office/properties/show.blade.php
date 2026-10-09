@extends('layouts.shell')
@section('content')
    <x-page-header :title="$property->name" :subtitle="trim(($property->city.' '.$property->address))">
        <x-slot:actions>
            @if (allows('units.manage'))
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createUnit"><i class="bi bi-plus-lg" aria-hidden="true"></i> Ajouter un logement</button>
            @endif
        </x-slot:actions>
    </x-page-header>
    @if ($property->description)
        <p class="mt-2 text-sm text-muted">{{ $property->description }}</p>
    @endif
    @if (allows('units.manage'))
        <div class="modal fade" id="createUnit" tabindex="-1" aria-labelledby="createUnitLabel" @if ($errors->any()) data-open-on-load @endif>
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form method="POST" action="{{ route('office.units.store', $property) }}" enctype="multipart/form-data" class="modal-content">
                @csrf
                <div class="modal-header"><h2 class="modal-title h5" id="createUnitLabel">Ajouter un logement</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body d-grid gap-2">
                <input class="field" name="name" placeholder="Nom, ex. Appartement A" required>
                <input class="field" name="reference" placeholder="Référence (facultatif)">
                <select class="field" name="type">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                <input class="field" name="bedrooms" type="number" min="0" placeholder="Nombre de chambres">
                <input class="field" name="features" placeholder="Caractéristiques, séparées par des virgules">
                <textarea class="field" name="description" placeholder="Description"></textarea>
                <div class="grid grid-cols-2 gap-2">
                    <input class="field" name="price" placeholder="Prix" required>
                    <select class="field" name="currency">
                        @foreach ($currencies as $currency)
                            <option>{{ $currency }}</option>
                        @endforeach
                    </select>
                </div>
                <input class="field" type="file" name="photo" accept="image/*">
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary">Ajouter</button></div>
            </form>
            </div>
        </div>
    @endif
    <div class="mt-4 space-y-2">
        @foreach ($property->units as $unit)
            <a class="card block" href="{{ route('office.units.show', $unit) }}">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold">{{ $unit->name }}</p>
                        <p class="text-sm text-muted">{{ $unit->reference }} · {{ $unit->type->label() }}</p>
                    </div>
                    <x-badge :tone="$unit->status->tone()">{{ $unit->status->label() }}</x-badge>
                </div>
                <p class="mt-2 font-semibold">{{ money($unit->price_minor, $unit->currency) }}</p>
            </a>
        @endforeach
    </div>
@endsection
