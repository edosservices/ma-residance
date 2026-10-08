@extends('layouts.shell')
@section('content')
    <p class="text-sm text-muted">{{ $invoice->number }} · {{ $invoice->type->label() }}</p>
    <div class="mt-1 flex items-start justify-between">
        <h1 class="text-2xl font-semibold">{{ $invoice->tenant->name }}</h1>
        <x-badge :tone="$invoice->status->tone()">{{ $invoice->status->label() }}</x-badge>
    </div>
    <p class="mt-3 text-3xl font-semibold">{{ money($balance, $invoice->currency) }}</p>
    <p class="text-sm text-muted">sur {{ money($invoice->amount_minor, $invoice->currency) }} · échéance {{ $invoice->due_on->format('d/m/Y') }}</p>
    <p class="text-sm text-muted">{{ $invoice->unit?->name }} · {{ $invoice->period_start->format('d/m') }} – {{ $invoice->period_end->format('d/m/Y') }}</p>
    @if ($invoice->notes)
        <p class="mt-2 text-sm">{{ $invoice->notes }}</p>
    @endif
    @if (allows('payments.validate') && ! in_array($invoice->status->value, ['paid', 'cancelled'], true))
        <form method="POST" action="{{ route('office.payments.store') }}" enctype="multipart/form-data" class="card mt-4 space-y-2">
            @csrf
            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
            <p class="font-semibold">Déclarer un paiement</p>
            <input class="field" name="amount" value="{{ number_format($balance / 100, 2, '.', '') }}">
            <select class="field" name="method">
                <option value="cash">Espèces</option>
                <option value="transfer">Transfert</option>
                <option value="other">Autre</option>
            </select>
            <input class="field" name="note" placeholder="Note">
            <input class="field" type="file" name="proof">
            <button class="btn btn-primary w-full">Enregistrer</button>
        </form>
    @endif
    @if (allows('collections.record') && ! in_array($invoice->status->value, ['paid', 'cancelled'], true))
        <form method="POST" action="{{ route('office.collections.store') }}" class="card mt-3 space-y-2">
            @csrf
            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
            <p class="font-semibold">Encaisser en espèces</p>
            <select class="field" name="agent_id">
                @foreach ($agents as $agent)
                    <option value="{{ $agent->user_id }}" @selected($agent->user_id === auth()->id())>{{ $agent->user->name }}</option>
                @endforeach
            </select>
            <input class="field" name="amount" value="{{ number_format($balance / 100, 2, '.', '') }}">
            <button class="btn btn-ghost w-full">Ouvrir l'encaissement</button>
        </form>
    @endif
    @if (allows('invoices.manage') && $balance === $invoice->amount_minor && $invoice->status->value !== 'cancelled')
        <form method="POST" action="{{ route('office.invoices.cancel', $invoice) }}" class="mt-3" data-confirm="Annuler cette facture ?">
            @csrf
            <input class="field" name="reason" placeholder="Motif d'annulation" required>
            <button class="btn btn-ghost mt-2 w-full">Annuler la facture</button>
        </form>
    @endif
@endsection
