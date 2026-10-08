@extends('layouts.shell')
@section('content')
    <p class="text-sm text-muted">{{ $property->city }} {{ $property->address }}</p>
    <h1 class="text-2xl font-semibold">{{ $property->name }}</h1>
    @if ($property->description)
        <p class="mt-2 text-sm text-muted">{{ $property->description }}</p>
    @endif
    @if (allows('units.manage'))
        <details class="card mt-4" open>
            <summary class="cursor-pointer font-semibold">Ajouter un logement</summary>
            <form method="POST" action="{{ route('office.units.store', $property) }}" enctype="multipart/form-data" class="mt-3 space-y-2">
                @csrf
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
                <button class="btn btn-primary w-full">Ajouter</button>
            </form>
        </details>
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
