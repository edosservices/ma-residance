@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Demandes</h1>
    <div class="mt-4 space-y-2">
        @forelse ($requests as $requestItem)
            <a class="card block" href="{{ route('office.requests.show', $requestItem) }}">
                <div class="flex justify-between gap-2">
                    <p class="font-semibold">{{ $requestItem->user->name }}</p>
                    <x-badge :tone="$requestItem->status->tone()">{{ $requestItem->status->label() }}</x-badge>
                </div>
                <p class="text-sm text-muted">{{ $requestItem->unit->property->name }} · {{ $requestItem->unit->name }}</p>
            </a>
        @empty
            <p class="text-sm text-muted">Aucune demande.</p>
        @endforelse
    </div>
@endsection
