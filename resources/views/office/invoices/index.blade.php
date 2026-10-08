@extends('layouts.shell')
@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Factures</h1>
        @if (allows('invoices.manage'))
            <a class="text-sm font-semibold text-brand" href="{{ route('office.utilities.create') }}">Eau / électricité</a>
        @endif
    </div>
    <form class="mt-3 flex gap-2" method="GET">
        <select class="field" name="statut" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            @foreach (['open' => 'À payer', 'partial' => 'Partiel', 'paid' => 'Payé', 'overdue' => 'En retard', 'cancelled' => 'Annulé'] as $value => $label)
                <option value="{{ $value }}" @selected(request('statut') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
    <div class="mt-4 space-y-2">
        @foreach ($invoices as $invoice)
            <a class="card block" href="{{ route('office.invoices.show', $invoice) }}">
                <div class="flex justify-between gap-2">
                    <p class="font-semibold">{{ $invoice->tenant->name }}</p>
                    <x-badge :tone="$invoice->status->tone()">{{ $invoice->status->label() }}</x-badge>
                </div>
                <p class="text-sm text-muted">{{ $invoice->type->label() }} · {{ $invoice->period_key }} · {{ $invoice->unit?->reference }}</p>
                <p class="mt-1">{{ money($invoice->balanceMinor(), $invoice->currency) }} restants sur {{ money($invoice->amount_minor, $invoice->currency) }}</p>
            </a>
        @endforeach
    </div>
    <div class="mt-4">{{ $invoices->links() }}</div>
@endsection
