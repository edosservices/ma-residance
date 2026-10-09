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
            <div class="field-group mb-2">
                <label for="amount">Montant</label>
                <input id="amount" class="field" name="amount" inputmode="decimal" value="{{ old('amount', number_format($balance / 100, 2, '.', '')) }}" required>
                <span class="field-hint">Au plus le solde restant, {{ money($balance, $invoice->currency) }}.</span>
            </div>
            <div class="field-group mb-2">
                <label for="method">Moyen</label>
                <select id="method" class="field" name="method" required>
                    <option value="transfer" @selected(old('method', 'transfer') === 'transfer')>Transfert</option>
                    <option value="other" @selected(old('method') === 'other')>Autre</option>
                </select>
                <span class="field-hint">Les espèces passent par un agent, dans le formulaire suivant.</span>
            </div>
            <div class="field-group mb-2">
                <label for="proof">Preuve du paiement</label>
                <input id="proof" class="field" type="file" name="proof" accept="image/*,.pdf" required>
                <span class="field-hint">Photo ou PDF du reçu, 4 Mo maximum. Obligatoire pour la confirmation.</span>
            </div>
            <div class="field-group mb-3">
                <label for="note">Précision</label>
                <textarea id="note" class="field" name="note" maxlength="500">{{ old('note') }}</textarea>
                <span class="field-hint">Référence du transfert ou nom de l’expéditeur, si vous l’avez.</span>
            </div>
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
                    <input id="cash-amount" class="field" name="amount" inputmode="decimal" value="{{ old('amount', number_format($balance / 100, 2, '.', '')) }}" required>
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
            @include('partials.payment-trace', ['payment' => $payment])
        </article>
    @empty
        <p class="text-muted">Aucun paiement enregistré sur cette facture.</p>
    @endforelse
@endsection
