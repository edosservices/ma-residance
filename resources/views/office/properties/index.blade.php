@extends('layouts.shell')
@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Propriétés</h1>
    </div>
    @if (allows('properties.manage'))
        <details class="card mt-4">
            <summary class="cursor-pointer font-semibold">Ajouter une propriété</summary>
            <form method="POST" action="{{ route('office.properties.store') }}" enctype="multipart/form-data" class="mt-3 space-y-2">
                @csrf
                <input class="field" name="name" placeholder="Nom, ex. Chez Omar" required>
                <input class="field" name="city" placeholder="Ville">
                <input class="field" name="address" placeholder="Adresse">
                <textarea class="field" name="description" placeholder="Description"></textarea>
                <input class="field" type="file" name="photo" accept="image/*">
                <button class="btn btn-primary w-full">Enregistrer</button>
            </form>
        </details>
    @endif
    <div class="mt-4 space-y-2">
        @foreach ($properties as $property)
            <a class="card block" href="{{ route('office.properties.show', $property) }}">
                <p class="font-semibold">{{ $property->name }}</p>
                <p class="text-sm text-muted">{{ $property->city }} · {{ $property->units_count }} logements</p>
            </a>
        @endforeach
    </div>
    <div class="mt-4">{{ $properties->links() }}</div>
@endsection
