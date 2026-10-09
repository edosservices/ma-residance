@extends('layouts.shell')
@section('content')
    <x-page-header title="Recouvrement" subtitle="Espèces confirmées et factures encore ouvertes">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('office.collections.index') }}"><i class="bi bi-cash-coin" aria-hidden="true"></i> Encaisser</a>
        </x-slot:actions>
    </x-page-header>
    <x-chart class="mb-2" :series="$work_series" :currency="$chart_currency" left="Espèces confirmées" :show-right="false" :show-net="false" />
    <p class="small text-muted mb-4">Ce graphique compte les espèces que vous avez confirmées. Il ne remplace pas les encaissements approuvés du bailleur.</p>
    @if (allows('payments.view') || allows('payments.validate'))
        <h2 class="h6 text-uppercase text-muted">Paiements à confirmer</h2>
        @forelse ($pending_payments as $payment)
            <a class="card d-block mb-2" href="{{ route('office.payments.show', $payment) }}">
                <div class="d-flex justify-content-between gap-2">
                    <strong>{{ $payment->tenant?->name }}</strong>
                    <x-badge tone="warn">À confirmer</x-badge>
                </div>
                <p class="mb-0">{{ money($payment->amount_minor, $payment->currency) }}</p>
            </a>
        @empty
            <p class="text-muted">Aucune preuve en attente.</p>
        @endforelse
    @endif
    <section class="mt-4">
        <h2 class="h6 text-uppercase text-muted">Espèces à remettre</h2>
        @forelse (collect($held)->where('agent_id', auth()->id()) as $row)
            <div class="card mb-2">
                <p class="h3 mb-2">{{ money($row['amount'], $row['currency']) }}</p>
                <form method="POST" action="{{ route('office.remittances.store') }}">
                    @csrf
                    <input type="hidden" name="currency" value="{{ $row['currency'] }}">
                    <button class="btn btn-primary">Remettre les fonds</button>
                </form>
            </div>
        @empty
            <p class="text-muted">Vous ne détenez aucune espèce.</p>
        @endforelse
    </section>
    <section class="mt-4">
        <h2 class="h6 text-uppercase text-muted">À recouvrer</h2>
        @forelse ($overdue as $invoice)
            <a class="card d-block mb-2" href="{{ route('office.invoices.show', $invoice) }}">
                <p class="font-semibold mb-0">{{ $invoice->tenant->name }}</p>
                <p class="text-muted mb-1">{{ $invoice->unit?->name }}</p>
                <p class="h5 mb-0">{{ money($invoice->balanceMinor(), $invoice->currency) }}</p>
                <p class="text-muted mb-0">Retard : {{ $invoice->days_late }} jour{{ $invoice->days_late > 1 ? 's' : '' }}</p>
            </a>
        @empty
            <p class="text-muted">Pas de facture en retard.</p>
        @endforelse
    </section>
@endsection
