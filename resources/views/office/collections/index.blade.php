@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Encaissements</h1>
    @if (allows('collections.record'))
        <form method="POST" action="{{ route('office.collections.store') }}" class="card mt-4 space-y-2">
            @csrf
            <div class="field-group mb-2">
                <label for="collection-invoice">Facture</label>
                <select id="collection-invoice" class="field" name="invoice_id" required>
                    <option value="">Ex. Jean Dupont · FAC-00001 · 150.00 USD</option>
                    @foreach ($invoices as $invoice)
                        <option value="{{ $invoice->id }}">{{ $invoice->tenant->name }} · {{ $invoice->number }} · {{ money($invoice->amount_minor, $invoice->currency) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-group mb-2">
                <label for="collection-agent">Agent</label>
                <select id="collection-agent" class="field" name="agent_id" required>
                    <option value="" disabled @selected(old('agent_id') === null)>Ex. Sarah, agent de recouvrement</option>
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->user_id }}" @selected((string) old('agent_id') === (string) $agent->user_id)>{{ $agent->user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-group mb-2">
                <label for="collection-amount">Montant reçu</label>
                <input id="collection-amount" class="field" name="amount" inputmode="decimal" value="{{ old('amount') }}" placeholder="Ex. 150.00" required>
                <span class="field-hint">Ex. 150.00, le montant remis en espèces.</span>
            </div>
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
