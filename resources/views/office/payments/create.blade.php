@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Déclarer un paiement</h1>
    <form method="POST" action="{{ route('office.payments.store') }}" enctype="multipart/form-data" class="mt-4 space-y-2">
        @csrf
        <select class="field" name="invoice_id" required>
            @foreach ($invoices as $invoice)
                <option value="{{ $invoice->id }}">{{ $invoice->number }} · {{ $invoice->tenant->name }} · {{ money($invoice->amount_minor, $invoice->currency) }}</option>
            @endforeach
        </select>
        <input class="field" name="amount" placeholder="Montant" required>
        <select class="field" name="method">
            <option value="cash">Espèces</option>
            <option value="transfer">Transfert</option>
            <option value="other">Autre</option>
        </select>
        <input class="field" name="note" placeholder="Note">
        <input class="field" type="file" name="proof">
        <button class="btn btn-primary w-full">Déclarer</button>
    </form>
@endsection
