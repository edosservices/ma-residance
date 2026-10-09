@extends('layouts.shell')
@section('content')
    <x-page-header title="Paiements" subtitle="Déclarations, validations et corrections">
        <x-slot:actions>
            @if (allows('payments.validate') || allows('collections.record'))
                <a class="btn btn-primary" href="{{ route('office.payments.create') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i> Enregistrer un paiement</a>
            @endif
        </x-slot:actions>
    </x-page-header>
    <div class="mt-4 space-y-2">
        @foreach ($payments as $payment)
            <a class="card block" href="{{ route('office.payments.show', $payment) }}">
                <div class="flex justify-between gap-2">
                    <p class="font-semibold">{{ $payment->reference }}</p>
                    <x-badge :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-badge>
                </div>
                <p class="text-sm text-muted">{{ $payment->tenant->name }} · {{ $payment->method->label() }} · {{ $payment->kind->label() }}</p>
                <p class="mt-1">{{ money($payment->amount_minor, $payment->currency) }}</p>
            </a>
        @endforeach
    </div>
    <div class="mt-4">{{ $payments->links() }}</div>
@endsection
