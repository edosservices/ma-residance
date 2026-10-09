@extends('layouts.shell')
@section('content')
    <p class="text-sm text-muted">{{ $invoice->number }} · {{ $invoice->type->label() }}</p>
    <h1 class="text-3xl font-semibold">{{ money($balance, $invoice->currency) }}</h1>
    <x-badge class="mt-2" :tone="$invoice->status->tone()">{{ $invoice->status->value === 'paid' ? 'Déjà payé' : $invoice->status->label() }}</x-badge>
    <p class="mt-2 text-sm text-muted">Facturé {{ money($invoice->amount_minor, $invoice->currency) }} · échéance {{ $invoice->due_on->format('d/m/Y') }}</p>
    @if (! in_array($invoice->status->value, ['paid', 'cancelled'], true))
        <form method="POST" action="{{ route('portal.payments.declare', $invoice) }}" enctype="multipart/form-data" class="card mt-4">
            @csrf
            <p class="kicker">Envoyer une preuve</p>
            <h2 class="h5">Déclarer un paiement</h2>
            <p class="text-muted">Le bailleur ou un collaborateur autorisé confirme ensuite. Tant que le paiement n’est pas approuvé, il n’entre pas dans les encaissements.</p>
            <x-payment-declare-fields
                prefix="portal"
                :amount-placeholder="'Ex. '.number_format($balance / 100, 2, '.', '')"
                :amount-hint="'Ex. '.number_format($balance / 100, 2, '.', '').'. Au plus le solde restant, '.money($balance, $invoice->currency).'.'"
                note-placeholder="Ex. Transfert du 9 octobre, référence 123456"
                :proof-required="true"
                :methods="['transfer' => 'Transfert', 'other' => 'Autre']"
            />
            <button class="btn btn-primary w-100">Envoyer pour confirmation</button>
        </form>
        @if ($agents->isNotEmpty())
            <form method="POST" action="{{ route('portal.payments.cash', $invoice) }}" class="card mt-3">
                @csrf
                <p class="font-semibold">Payer en espèces à un agent</p>
                <p class="text-muted">L’agent confirme la réception. Les deux confirmations créent le paiement approuvé.</p>
                <div class="field-group mb-2">
                    <label for="agent_id">Agent</label>
                    <select id="agent_id" class="field" name="agent_id" required>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->user_id }}">{{ $agent->user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-group mb-3">
                    <label for="cash-amount">Montant remis</label>
                    <input id="cash-amount" class="field" name="amount" inputmode="decimal" value="{{ old('amount') }}" placeholder="Ex. {{ number_format($balance / 100, 2, '.', '') }}" required>
                    <span class="field-hint">Ex. {{ number_format($balance / 100, 2, '.', '') }}, le montant remis à l'agent.</span>
                </div>
                <button class="btn btn-ghost w-100">Je remets les espèces</button>
            </form>
        @endif
    @endif
    <h2 class="h6 text-uppercase text-muted mt-4">Traçabilité</h2>
    @forelse ($payments as $payment)
        <article class="card mb-2">
            <div class="d-flex justify-content-between gap-2">
                <strong>{{ $payment->reference }} · {{ money($payment->amount_minor, $payment->currency) }}</strong>
                <x-badge :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-badge>
            </div>
            @if (sees_trace())
                @include('partials.payment-trace', ['payment' => $payment])
            @else
                <p class="text-muted mb-0 mt-2">Le détail de cette déclaration reste chez le bailleur.</p>
            @endif
        </article>
    @empty
        <p class="text-muted">Aucun paiement enregistré sur cette facture.</p>
    @endforelse
@endsection
