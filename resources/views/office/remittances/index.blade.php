@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Remises</h1>
    @if (allows('collections.remit'))
        <form method="POST" action="{{ route('office.remittances.store') }}" class="mt-4 flex gap-2">
            @csrf
            <select class="field" name="currency">
                <option>USD</option>
                <option>CDF</option>
            </select>
            <button class="btn btn-primary">Remettre</button>
        </form>
    @endif
    <div class="mt-4 space-y-2">
        @foreach ($remittances as $remittance)
            <a class="card block" href="{{ route('office.remittances.show', $remittance) }}">
                <div class="flex justify-between">
                    <p class="font-semibold">{{ $remittance->reference }}</p>
                    <x-badge :tone="$remittance->status->tone()">{{ $remittance->status->label() }}</x-badge>
                </div>
                <p class="text-sm text-muted">{{ $remittance->agent->name }}</p>
                <p class="mt-1">{{ money($remittance->amount_minor, $remittance->currency) }}</p>
            </a>
        @endforeach
    </div>
@endsection
