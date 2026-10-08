@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">{{ $tenant->name }}</h1>
    <p class="text-sm text-muted">{{ $tenant->phone }} @if($tenant->email) · {{ $tenant->email }} @endif</p>
    @if (allows('tenants.manage') && $tenant->notes)
        <p class="card mt-3 text-sm">{{ $tenant->notes }}</p>
    @endif
    <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-muted">Contrats</h2>
    <div class="mt-2 space-y-2">
        @foreach ($tenant->contracts as $contract)
            @if (allows('contracts.view') || allows('contracts.manage'))
                <a class="card block" href="{{ route('office.contracts.show', $contract) }}">
                    <p class="font-semibold">{{ $contract->reference }} · {{ $contract->unit->name }}</p>
                    <x-badge :tone="$contract->status->tone()">{{ $contract->status->label() }}</x-badge>
                </a>
            @else
                <div class="card">
                    <p class="font-semibold">{{ $contract->unit->name }}</p>
                    <p class="text-sm">{{ money($contract->rent_minor, $contract->currency) }}</p>
                </div>
            @endif
        @endforeach
    </div>
    <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-muted">Factures</h2>
    <div class="mt-2 space-y-2">
        @foreach ($tenant->invoices as $invoice)
            <a class="card block" href="{{ route('office.invoices.show', $invoice) }}">
                <div class="flex justify-between gap-2">
                    <p class="font-semibold">{{ $invoice->type->label() }} · {{ $invoice->period_key }}</p>
                    <x-badge :tone="$invoice->status->tone()">{{ $invoice->status->label() }}</x-badge>
                </div>
                <p class="mt-1">{{ money($invoice->amount_minor, $invoice->currency) }}</p>
            </a>
        @endforeach
    </div>
@endsection
