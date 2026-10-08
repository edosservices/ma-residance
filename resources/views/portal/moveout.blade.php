@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Quitter le logement</h1>
    @if ($contract && in_array($contract->status->value, ['active', 'move_out_requested'], true))
        <p class="mt-2 text-sm text-muted">{{ $contract->unit->name }} reste occupé jusqu'à la validation du bailleur.</p>
        @if ($contract->status->value === 'active')
            <form method="POST" action="{{ route('portal.moveout.store') }}" class="mt-4 space-y-2">
                @csrf
                <input class="field" type="date" name="planned_on" required>
                <textarea class="field" name="reason" placeholder="Motif facultatif"></textarea>
                <button class="btn btn-primary w-full">Je souhaite quitter ce logement</button>
            </form>
        @else
            <p class="card mt-4">Votre demande de départ est en cours.</p>
        @endif
    @else
        <p class="card mt-4">Aucun logement actif.</p>
    @endif
@endsection
