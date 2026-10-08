@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Eau ou électricité</h1>
    <p class="mt-2 text-sm text-muted">Le montant global est réparti. Le choix par personne ou par logement peut s'étendre plus tard à un compteur.</p>
    <form method="POST" action="{{ route('office.utilities.store') }}" class="mt-4 space-y-2">
        @csrf
        <select class="field" name="property_id" required>
            @foreach ($properties as $property)
                <option value="{{ $property->id }}">{{ $property->name }}</option>
            @endforeach
        </select>
        <select class="field" name="type">
            <option value="water">Eau</option>
            <option value="electricity">Électricité</option>
        </select>
        <input class="field" name="month" type="month" value="{{ now()->format('Y-m') }}" required>
        <input class="field" name="total" placeholder="Montant" required>
        <p class="text-xs text-muted">Par personne ou par logement : montant global à répartir. Montant fixe : chaque logement occupé est facturé de ce montant.</p>
        <select class="field" name="currency">
            @foreach ($currencies as $currency)
                <option>{{ $currency }}</option>
            @endforeach
        </select>
        <select class="field" name="method">
            <option value="per_person">Par personne</option>
            <option value="per_unit">Par logement</option>
            <option value="fixed">Montant fixe par logement occupé</option>
        </select>
        <button class="btn btn-primary w-full">Répartir</button>
    </form>
@endsection
