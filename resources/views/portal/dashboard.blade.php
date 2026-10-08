@extends('layouts.shell')
@section('content')
    <p class="text-sm text-muted">{{ $contract?->unit?->property?->name }}</p>
    <h1 class="text-3xl font-semibold">{{ $contract?->unit?->name ?? 'Pas encore de logement' }}</h1>
    @if ($contract)
        <p class="mt-2 text-sm text-muted">Contrat {{ $contract->reference }} · {{ $contract->status->label() }}</p>
        <p class="mt-4 text-sm text-muted">Loyer</p>
        <p class="text-3xl font-semibold">{{ money($contract->rent_minor, $contract->currency) }}</p>
    @endif
    <div class="mt-4 grid grid-cols-2 gap-3">
        <x-stat label="À payer" :value="money($due, $currency)" />
        <x-stat label="Retard" :value="$daysLate > 0 ? $daysLate.' j' : '0'" />
    </div>
    @if ($next)
        <p class="mt-3 text-sm text-muted">Prochaine échéance {{ $next->due_on->format('d/m/Y') }}</p>
    @endif
    <div class="mt-4 grid grid-cols-2 gap-2">
        <a class="btn btn-primary" href="{{ route('portal.invoices.index') }}">Mes factures</a>
        <a class="btn btn-ghost" href="{{ route('portal.maintenance.index') }}">Signaler</a>
        <a class="btn btn-ghost" href="{{ route('portal.messages.index') }}">Message</a>
        <a class="btn btn-ghost" href="{{ route('portal.moveout.create') }}">Je souhaite partir</a>
    </div>
    <div class="mt-5 space-y-2">
        @foreach ($invoices as $invoice)
            <a class="card block" href="{{ route('portal.invoices.show', $invoice) }}">
                <div class="flex justify-between gap-2">
                    <p class="font-semibold">{{ $invoice->type->label() }} · {{ $invoice->period_key }}</p>
                    <x-badge :tone="$invoice->status->tone()">{{ $invoice->status->label() }}</x-badge>
                </div>
                <p class="mt-1">{{ money($invoice->balanceMinor(), $invoice->currency) }}</p>
            </a>
        @endforeach
    </div>
@endsection
