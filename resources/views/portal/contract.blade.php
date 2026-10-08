@extends('layouts.shell')
@section('content')
    @if ($contract)
        <h1 class="text-2xl font-semibold">{{ $contract->reference }}</h1>
        <p class="mt-2">{{ $contract->unit->property->name }} · {{ $contract->unit->name }}</p>
        <p class="mt-3 text-3xl font-semibold">{{ money($contract->rent_minor, $contract->currency) }}</p>
        <p class="text-sm text-muted">Depuis le {{ $contract->start_date->format('d/m/Y') }}</p>
        <x-badge class="mt-3" :tone="$contract->status->tone()">{{ $contract->status->label() }}</x-badge>
        @if ($contract->conditions)
            <p class="card mt-4 text-sm">{{ $contract->conditions }}</p>
        @endif
    @else
        <p class="card">Aucun contrat pour le moment.</p>
        <a class="btn btn-primary mt-4" href="{{ route('catalog.index') }}">Voir les logements</a>
    @endif
@endsection
