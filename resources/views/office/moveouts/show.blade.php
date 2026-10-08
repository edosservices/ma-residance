@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Départ</h1>
    <p class="mt-2">{{ $moveOut->tenant->name }} quitte {{ $moveOut->contract->unit->name }} le {{ $moveOut->planned_on->format('d/m/Y') }}.</p>
    <p class="text-sm text-muted">Demandé le {{ $moveOut->requested_on->format('d/m/Y') }}. Solde restant : {{ money($balance, $moveOut->contract->currency) }}</p>
    @if ($moveOut->reason)
        <p class="card mt-3 text-sm">{{ $moveOut->reason }}</p>
    @endif
    @if ($moveOut->status->value === 'requested')
        <form method="POST" action="{{ route('office.moveouts.complete', $moveOut) }}" enctype="multipart/form-data" class="card mt-4 space-y-2">
            @csrf
            <label class="flex gap-2 text-sm"><input type="checkbox" name="payments_checked" value="1" required> Paiements vérifiés</label>
            <label class="flex gap-2 text-sm"><input type="checkbox" name="debts_checked" value="1" required> Dettes vérifiées</label>
            <label class="flex gap-2 text-sm"><input type="checkbox" name="condition_checked" value="1" required> État du logement vérifié</label>
            <label class="flex gap-2 text-sm"><input type="checkbox" name="equipment_checked" value="1" required> Équipements vérifiés</label>
            <textarea class="field" name="review_note" placeholder="Note"></textarea>
            <input class="field" type="file" name="photo" accept="image/*">
            <button class="btn btn-primary w-full">Valider la sortie</button>
        </form>
        <form method="POST" action="{{ route('office.moveouts.reject', $moveOut) }}" class="mt-3 space-y-2">
            @csrf
            <input class="field" name="review_note" placeholder="Motif du refus" required>
            <button class="btn btn-ghost w-full">Refuser le départ</button>
        </form>
    @else
        <x-badge class="mt-3" :tone="$moveOut->status->tone()">{{ $moveOut->status->label() }}</x-badge>
    @endif
@endsection
