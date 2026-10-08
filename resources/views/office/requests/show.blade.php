@extends('layouts.shell')
@section('content')
    <x-badge :tone="$rentalRequest->status->tone()">{{ $rentalRequest->status->label() }}</x-badge>
    <h1 class="mt-2 text-2xl font-semibold">{{ $rentalRequest->unit->name }}</h1>
    <p class="text-sm text-muted">{{ $rentalRequest->user->name }} · {{ $rentalRequest->user->phone }}</p>
    @if ($rentalRequest->message)
        <p class="card mt-3 text-sm">{{ $rentalRequest->message }}</p>
    @endif
    @if ($rentalRequest->status->value === 'pending' && allows('contracts.manage'))
        <form method="POST" action="{{ route('office.requests.accept', $rentalRequest) }}" class="card mt-4 space-y-2">
            @csrf
            <p class="font-semibold">Accepter et créer le contrat</p>
            <input class="field" type="date" name="start_date" value="{{ now()->toDateString() }}" required>
            <input class="field" type="date" name="end_date">
            <input class="field" name="rent" value="{{ number_format($rentalRequest->unit->price_minor / 100, 2, '.', '') }}" required>
            <select class="field" name="currency">
                @foreach ($currencies as $currency)
                    <option @selected($currency === $rentalRequest->unit->currency)>{{ $currency }}</option>
                @endforeach
            </select>
            <textarea class="field" name="conditions" placeholder="Conditions"></textarea>
            <button class="btn btn-primary w-full">Accepter</button>
        </form>
        <form method="POST" action="{{ route('office.requests.refuse', $rentalRequest) }}" class="mt-3">
            @csrf
            <input class="field" name="decision_note" placeholder="Motif du refus (facultatif)">
            <button class="btn btn-ghost mt-2 w-full">Refuser</button>
        </form>
    @endif
@endsection
