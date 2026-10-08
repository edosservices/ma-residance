@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Paiements</h1>
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
