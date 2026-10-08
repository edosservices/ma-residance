@extends('layouts.shell')
@section('content')
    <p class="text-sm text-muted">{{ $collection->reference }} · code {{ $collection->code }}</p>
    <h1 class="text-3xl font-semibold">{{ money($collection->amount_minor, $collection->currency) }}</h1>
    <x-badge class="mt-2" :tone="$collection->status->tone()">{{ $collection->status->label() }}</x-badge>
    <div class="card mt-4 space-y-2 text-sm">
        <p>Locataire : <strong>{{ $collection->tenant->name }}</strong> {{ $collection->tenant_confirmed_at ? '· confirmé' : '· en attente' }}</p>
        <p>Agent : <strong>{{ $collection->agent->name }}</strong> {{ $collection->agent_confirmed_at ? '· confirmé' : '· en attente' }}</p>
        <p>Facture : {{ $collection->invoice->number }}</p>
        @if ($collection->payment)
            <p>Reçu : {{ $collection->payment->reference }}</p>
        @endif
    </div>
    @if ($collection->status->value === 'awaiting_confirmation' && (int) $collection->agent_id === (int) auth()->id() && ! $collection->agent_confirmed_at)
        <form method="POST" action="{{ route('office.collections.confirm', $collection) }}" class="mt-4">
            @csrf
            <button class="btn btn-primary w-full">J'ai reçu {{ money($collection->amount_minor, $collection->currency) }}</button>
        </form>
    @endif
    @if ($collection->status->value === 'awaiting_confirmation' && allows('collections.record'))
        <form method="POST" action="{{ route('office.collections.cancel', $collection) }}" class="mt-2">
            @csrf
            <button class="btn btn-ghost w-full">Annuler l'encaissement</button>
        </form>
    @endif
@endsection
