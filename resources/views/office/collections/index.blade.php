@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Encaissements</h1>
    @if (allows('collections.record'))
        <form method="POST" action="{{ route('office.collections.store') }}" class="card mt-4 space-y-2">
            @csrf
            <select class="field" name="invoice_id" required>
                <option value="">Facture</option>
                @foreach ($invoices as $invoice)
                    <option value="{{ $invoice->id }}">{{ $invoice->tenant->name }} · {{ $invoice->number }} · {{ money($invoice->amount_minor, $invoice->currency) }}</option>
                @endforeach
            </select>
            <select class="field" name="agent_id">
                @foreach ($agents as $agent)
                    <option value="{{ $agent->user_id }}" @selected($agent->user_id === auth()->id())>{{ $agent->user->name }}</option>
                @endforeach
            </select>
            <input class="field" name="amount" placeholder="Montant reçu" required>
            <button class="btn btn-primary w-full">J'ouvre l'encaissement</button>
        </form>
    @endif
    <div class="mt-4 space-y-2">
        @foreach ($collections as $collection)
            <a class="card block" href="{{ route('office.collections.show', $collection) }}">
                <div class="flex justify-between gap-2">
                    <p class="font-semibold">{{ $collection->code }}</p>
                    <x-badge :tone="$collection->status->tone()">{{ $collection->status->label() }}</x-badge>
                </div>
                <p class="text-sm text-muted">{{ $collection->tenant->name }} → {{ $collection->agent->name }}</p>
                <p class="mt-1">{{ money($collection->amount_minor, $collection->currency) }}</p>
            </a>
        @endforeach
    </div>
@endsection
