@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Recouvrement</h1>
    <a class="btn btn-primary mt-4 w-full" href="{{ route('office.collections.index') }}">Encaisser</a>
    <section class="mt-5 space-y-2">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-muted">Espèces à remettre</h2>
        @forelse (collect($held)->where('agent_id', auth()->id()) as $row)
                <div class="card">
                    <p class="text-3xl font-semibold">{{ money($row['amount'], $row['currency']) }}</p>
                    <form method="POST" action="{{ route('office.remittances.store') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="currency" value="{{ $row['currency'] }}">
                        <button class="btn btn-primary w-full">Remettre les fonds</button>
                    </form>
                </div>
        @empty
            <p class="text-sm text-muted">Vous ne détenez aucune espèce.</p>
        @endforelse
    </section>
    <section class="mt-6 space-y-2">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-muted">À recouvrer</h2>
        @forelse ($overdue as $invoice)
            <a class="card block" href="{{ route('office.invoices.show', $invoice) }}">
                <p class="font-semibold">{{ $invoice->tenant->name }}</p>
                <p class="text-sm text-muted">{{ $invoice->unit?->name }}</p>
                <p class="mt-1 text-lg font-semibold">{{ money($invoice->balanceMinor(), $invoice->currency) }}</p>
                <p class="text-sm text-muted">Retard : {{ $invoice->days_late }} jour{{ $invoice->days_late > 1 ? 's' : '' }}</p>
            </a>
        @empty
            <p class="text-sm text-muted">Pas de facture en retard.</p>
        @endforelse
    </section>
@endsection
