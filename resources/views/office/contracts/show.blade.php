@extends('layouts.shell')
@section('content')
    <div class="flex items-start justify-between gap-2">
        <h1 class="text-2xl font-semibold">{{ $contract->reference }}</h1>
        <x-badge :tone="$contract->status->tone()">{{ $contract->status->label() }}</x-badge>
    </div>
    <p class="mt-2 text-sm text-muted">{{ $contract->tenant->name }} · {{ $contract->unit->property->name }} · {{ $contract->unit->name }}</p>
    <p class="mt-3 text-3xl font-semibold">{{ money($contract->rent_minor, $contract->currency) }}</p>
    @if ($contract->equivalent_minor)
        <p class="text-sm text-muted">Équivalent figé : {{ money($contract->equivalent_minor, $contract->equivalent_currency) }} au taux {{ $contract->fx_rate }}</p>
    @endif
    <p class="mt-2 text-sm">Du {{ $contract->start_date->format('d/m/Y') }} @if($contract->end_date) au {{ $contract->end_date->format('d/m/Y') }} @else · sans date de fin @endif</p>
    <p class="text-sm text-muted">Échéance le {{ $contract->due_day }}, grâce jusqu'au {{ $contract->grace_until_day }}. Prorata : {{ $prorata[$contract->prorata_method] ?? $contract->prorata_method }}</p>
    @if ($contract->conditions)
        <p class="card mt-3 text-sm">{{ $contract->conditions }}</p>
    @endif
    @if (allows('contracts.manage') && ! in_array($contract->status->value, ['ended', 'cancelled'], true))
        <form method="POST" action="{{ route('office.contracts.update', $contract) }}" class="card mt-4 space-y-2">
            @csrf
            @method('PUT')
            <p class="text-sm font-semibold">Conditions. Le loyer reste {{ money($contract->rent_minor, $contract->currency) }}.</p>
            <input class="field" type="date" name="end_date" value="{{ $contract->end_date?->format('Y-m-d') }}">
            <input class="field" type="number" name="generation_day" value="{{ $contract->generation_day }}">
            <input class="field" type="number" name="due_day" value="{{ $contract->due_day }}">
            <input class="field" type="number" name="grace_until_day" value="{{ $contract->grace_until_day }}">
            <select class="field" name="prorata_method">
                @foreach ($prorata as $code => $label)
                    <option value="{{ $code }}" @selected($contract->prorata_method === $code)>{{ $label }}</option>
                @endforeach
            </select>
            <textarea class="field" name="conditions">{{ $contract->conditions }}</textarea>
            <button class="btn btn-ghost w-full">Mettre à jour</button>
        </form>
    @endif
    <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-muted">Factures</h2>
    <div class="mt-2 space-y-2">
        @foreach ($contract->invoices as $invoice)
            <a class="card block" href="{{ route('office.invoices.show', $invoice) }}">
                <p class="font-semibold">{{ $invoice->number }} · {{ $invoice->type->label() }}</p>
                <p class="text-sm">{{ money($invoice->amount_minor, $invoice->currency) }} · {{ $invoice->status->label() }}</p>
            </a>
        @endforeach
    </div>
@endsection
