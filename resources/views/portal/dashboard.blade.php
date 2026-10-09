@extends('layouts.shell')
@section('content')
    <p class="text-muted mb-1">{{ $contract?->unit?->property?->name }}</p>
    <x-page-header :title="$contract?->unit?->name ?? 'Pas encore de logement'" :subtitle="$contract ? 'Contrat '.$contract->reference.' · '.$contract->status->label() : null">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('portal.invoices.index') }}">Mes factures</a>
            <a class="btn btn-ghost" href="{{ route('portal.maintenance.index') }}">Signaler</a>
        </x-slot:actions>
    </x-page-header>
    @if ($contract)
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4"><x-stat label="Loyer" :value="money($contract->rent_minor, $contract->currency)" icon="house" tone="mauve" href="{{ route('portal.contract') }}" /></div>
            <div class="col-6 col-md-4"><x-stat label="Payé" :value="money($paid, $currency)" icon="check-circle" tone="green" /></div>
            <div class="col-6 col-md-4"><x-stat label="Reste à payer" :value="money($due, $currency)" icon="exclamation-circle" tone="warn" href="{{ route('portal.invoices.index') }}" /></div>
        </div>
        <x-stat class="mb-3" label="Retard" :value="$daysLate > 0 ? $daysLate.' jour'.($daysLate > 1 ? 's' : '') : 'À jour'" tone="{{ $daysLate > 0 ? 'bad' : 'green' }}" />
    @endif
    <div class="row g-3 mb-3">
        @foreach ($charges as $charge)
            <div class="col-12 col-md-6">
                <article class="card h-100">
                    <div class="d-flex justify-content-between gap-2">
                        <strong>{{ $charge['label'] }}</strong>
                        @if ($charge['status'])
                            <x-badge :tone="$charge['status']->tone()">{{ $charge['status']->label() }}</x-badge>
                        @endif
                    </div>
                    <p class="mb-0 mt-2">Payé {{ money($charge['paid'], $charge['currency']) }}</p>
                    <p class="mb-0">Reste {{ money($charge['due'], $charge['currency']) }}</p>
                </article>
            </div>
        @endforeach
    </div>
    @if ($next)
        <p class="text-muted">Prochaine échéance {{ $next->due_on->format('d/m/Y') }}</p>
    @endif
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-ghost" href="{{ route('portal.messages.index') }}"><i class="bi bi-chat-dots" aria-hidden="true"></i> Messages</a>
        <a class="btn btn-ghost" href="{{ route('portal.moveout.create') }}"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Je souhaite partir</a>
        <a class="btn btn-ghost" href="{{ route('portal.notifications.index') }}"><i class="bi bi-bell" aria-hidden="true"></i> Notifications</a>
    </div>
    <h2 class="h6 text-uppercase text-muted">Factures</h2>
    @forelse ($invoices as $invoice)
        <a class="card d-block mb-2" href="{{ route('portal.invoices.show', $invoice) }}">
            <div class="d-flex justify-content-between gap-2">
                <strong>{{ $invoice->type->label() }} · {{ $invoice->period_key }}</strong>
                <x-badge :tone="$invoice->status->tone()">{{ $invoice->status->label() }}</x-badge>
            </div>
            <p class="mb-0 mt-1">Payé {{ money($invoice->netPaidMinor(), $invoice->currency) }} · reste {{ money($invoice->balanceMinor(), $invoice->currency) }}</p>
        </a>
    @empty
        <x-empty title="Aucune facture" text="Vos loyers et charges apparaîtront ici." />
    @endforelse
@endsection
