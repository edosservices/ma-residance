@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Interventions</h1>
    <a class="btn btn-primary mt-4 w-full" href="{{ route('office.maintenance.index') }}">Voir les incidents</a>
    <div class="mt-4 space-y-2">
        @forelse ($maintenance as $item)
            <a class="card block" href="{{ route('office.maintenance.show', $item) }}">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-semibold">{{ $item->title }}</p>
                    <x-badge :tone="$item->urgency->tone()">{{ $item->urgency->label() }}</x-badge>
                </div>
                <p class="text-sm text-muted">{{ $item->unit->name }} · {{ $item->status->label() }}</p>
            </a>
        @empty
            <p class="card text-sm text-muted">Rien en cours.</p>
        @endforelse
    </div>
@endsection
