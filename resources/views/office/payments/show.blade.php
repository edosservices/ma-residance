@extends('layouts.shell')
@section('content')
    <p class="text-sm text-muted">{{ $payment->reference }} · {{ $payment->kind->label() }}</p>
    <h1 class="text-3xl font-semibold">{{ money($payment->amount_minor, $payment->currency) }}</h1>
    <x-badge class="mt-2" :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-badge>
    <p class="mt-3 text-sm">{{ $payment->tenant->name }} · {{ $payment->method->label() }}</p>
    @if ($payment->invoice)
        <a class="mt-2 block text-sm font-semibold text-brand" href="{{ route('office.invoices.show', $payment->invoice) }}">Facture {{ $payment->invoice->number }}</a>
    @endif
    @if ($payment->note)
        <p class="card mt-3 text-sm">{{ $payment->note }}</p>
    @endif
    @if ($payment->proof_path)
        <a class="mt-3 block text-sm font-semibold text-brand" href="{{ file_url($payment->proof_path) }}" target="_blank">Voir la preuve</a>
    @endif
    @if ($payment->status->value === 'pending' && allows('payments.validate'))
        <form method="POST" action="{{ route('office.payments.approve', $payment) }}" class="mt-4">
            @csrf
            <button class="btn btn-primary w-full">Valider</button>
        </form>
        <form method="POST" action="{{ route('office.payments.reject', $payment) }}" class="mt-2 space-y-2">
            @csrf
            <input class="field" name="reason" placeholder="Motif du rejet" required>
            <button class="btn btn-ghost w-full">Rejeter</button>
        </form>
    @endif
    @if ($payment->status->value === 'approved' && $payment->kind->value === 'payment' && ! $payment->reversal && allows('payments.validate'))
        <form method="POST" action="{{ route('office.payments.reverse', $payment) }}" class="mt-4 space-y-2" data-confirm="Créer une écriture de correction ? Le paiement d'origine reste en historique.">
            @csrf
            <input class="field" name="reason" placeholder="Motif de la correction" required>
            <button class="btn btn-ghost w-full">Corriger par une nouvelle écriture</button>
        </form>
    @endif
@endsection
