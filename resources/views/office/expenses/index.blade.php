@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Dépenses</h1>
    @if (allows('expenses.manage'))
        <form method="POST" action="{{ route('office.expenses.store') }}" enctype="multipart/form-data" class="card mt-4 space-y-2">
            @csrf
            <input class="field" name="amount" placeholder="Montant" required>
            <select class="field" name="currency">@foreach ($currencies as $currency)<option>{{ $currency }}</option>@endforeach</select>
            <select class="field" name="expense_category_id">@foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
            <input class="field" type="date" name="spent_on" value="{{ now()->toDateString() }}" required>
            <input class="field" name="motif" placeholder="Motif" required>
            <select class="field" name="property_id"><option value="">Propriété</option>@foreach ($properties as $property)<option value="{{ $property->id }}">{{ $property->name }}</option>@endforeach</select>
            <select class="field" name="unit_id"><option value="">Logement</option>@foreach ($units as $unit)<option value="{{ $unit->id }}">{{ $unit->reference }}</option>@endforeach</select>
            <select class="field" name="tenant_id"><option value="">Locataire</option>@foreach ($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->name }}</option>@endforeach</select>
            <input class="field" name="payee" placeholder="Technicien ou fournisseur">
            <textarea class="field" name="comment" placeholder="Commentaire"></textarea>
            <input class="field" type="file" name="attachment">
            <button class="btn btn-primary w-full">Enregistrer</button>
        </form>
    @endif
    <div class="mt-4 space-y-2">
        @foreach ($expenses as $expense)
            <article class="card">
                <div class="flex justify-between gap-2">
                    <p class="font-semibold">{{ $expense->motif }}</p>
                    <x-badge :tone="$expense->status->tone()">{{ $expense->status->label() }}</x-badge>
                </div>
                <p class="text-sm text-muted">{{ $expense->category->name }} · {{ $expense->spent_on->format('d/m/Y') }} · {{ $expense->unit?->reference ?? $expense->property?->name ?? 'Organisation' }}</p>
                <p class="mt-1">{{ money($expense->amount_minor, $expense->currency) }}</p>
                @if (allows('expenses.manage') && $expense->status->value === 'recorded')
                    <form method="POST" action="{{ route('office.expenses.void', $expense) }}" class="mt-2 flex gap-2">
                        @csrf
                        <input class="field" name="reason" placeholder="Motif d'annulation" required>
                        <button class="btn btn-ghost">Annuler</button>
                    </form>
                @endif
            </article>
        @endforeach
    </div>
@endsection
