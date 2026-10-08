@extends('layouts.shell')
@section('content')
    <p class="text-sm text-muted">{{ $invoice->number }} · {{ $invoice->type->label() }}</p>
    <h1 class="text-3xl font-semibold">{{ money($balance, $invoice->currency) }}</h1>
    <x-badge class="mt-2" :tone="$invoice->status->tone()">{{ $invoice->status->label() }}</x-badge>
    <p class="mt-2 text-sm text-muted">Facturé {{ money($invoice->amount_minor, $invoice->currency) }} · échéance {{ $invoice->due_on->format('d/m/Y') }}</p>
    @if (! in_array($invoice->status->value, ['paid', 'cancelled'], true))
        <form method="POST" action="{{ route('portal.payments.declare', $invoice) }}" enctype="multipart/form-data" class="card mt-4 space-y-2">
            @csrf
            <p class="font-semibold">Déclarer un paiement</p>
            <input class="field" name="amount" value="{{ number_format($balance / 100, 2, '.', '') }}">
            <select class="field" name="method"><option value="transfer">Transfert</option><option value="other">Autre</option></select>
            <input class="field" type="file" name="proof" accept="image/*,.pdf">
            <button class="btn btn-primary w-full">Envoyer pour validation</button>
        </form>
        <form method="POST" action="{{ route('portal.payments.cash', $invoice) }}" class="card mt-3 space-y-2">
            @csrf
            <p class="font-semibold">Payer en espèces à un agent</p>
            <select class="field" name="agent_id" required>
                @foreach ($agents as $agent)
                    <option value="{{ $agent->user_id }}">{{ $agent->user->name }}</option>
                @endforeach
            </select>
            <input class="field" name="amount" value="{{ number_format($balance / 100, 2, '.', '') }}">
            <button class="btn btn-ghost w-full">Je remets les espèces</button>
        </form>
    @endif
@endsection
