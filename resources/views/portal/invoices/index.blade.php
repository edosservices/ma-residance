@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Mes factures</h1>
    <div class="mt-4 space-y-2">
        @foreach ($invoices as $invoice)
            <a class="card block" href="{{ route('portal.invoices.show', $invoice) }}">
                <div class="flex justify-between"><p class="font-semibold">{{ $invoice->type->label() }}</p><x-badge :tone="$invoice->status->tone()">{{ $invoice->status->value === 'paid' ? 'Déjà payé' : $invoice->status->label() }}</x-badge></div>
                <p class="text-sm text-muted">{{ $invoice->period_key }} · échéance {{ $invoice->due_on->format('d/m/Y') }}</p>
                <p class="mt-1">{{ money($invoice->balanceMinor(), $invoice->currency) }}</p>
            </a>
        @endforeach
    </div>
@endsection
