@extends('layouts.shell')
@section('content')
    <x-badge :tone="$rentalRequest->status->tone()">{{ $rentalRequest->status->label() }}</x-badge>
    <h1 class="mt-2 text-2xl font-semibold">{{ $rentalRequest->unit->name }}</h1>
    <p class="text-sm text-muted">{{ $rentalRequest->user->name }} · {{ $rentalRequest->user->phone }}</p>
    @if ($rentalRequest->message)
        <p class="card mt-3 text-sm">{{ $rentalRequest->message }}</p>
    @endif
    @if ($rentalRequest->status->value === 'pending' && allows('contracts.manage'))
        <form method="POST" action="{{ route('office.requests.accept', $rentalRequest) }}" class="card mt-4 space-y-2" enctype="multipart/form-data">
            @csrf
            <p class="font-semibold">Accepter et créer le contrat</p>
            <p class="field-hint">Ex. début 09 oct. 2026, loyer 240.00, devise USD. La pièce jointe peut être une photo du contrat ou de la pièce d'identité.</p>
            <label class="block text-sm">Début du bail<input class="field" type="date" name="start_date" value="{{ now()->toDateString() }}" required><span class="field-hint">Ex. 09 oct. 2026.</span></label>
            <label class="block text-sm">Fin du bail<input class="field" type="date" name="end_date"><span class="field-hint">Facultatif. Ex. 09 oct. 2027.</span></label>
            <label class="block text-sm">Loyer d'un mois<input class="field" name="rent" value="{{ number_format($rentalRequest->unit->price_minor / 100, 2, '.', '') }}" placeholder="Ex. 240.00" required><span class="field-hint">Ex. 240.00, sans la garantie.</span></label>
            <label class="block text-sm">Devise
                <select class="field" name="currency">
                    @foreach ($currencies as $currency)
                        <option @selected($currency === $rentalRequest->unit->currency)>{{ $currency }}</option>
                    @endforeach
                </select>
                <span class="field-hint">Ex. USD.</span>
            </label>
            <label class="block text-sm">Conditions<textarea class="field" name="conditions" placeholder="Ex. Paiement au plus tard le 5."></textarea></label>
            <label class="block text-sm">Pièce jointe<input class="field" type="file" name="attachment" accept="image/*,.pdf"><span class="field-hint">Facultatif. Ex. photo du contrat signé.</span></label>
            <button class="btn btn-primary w-full">Accepter</button>
        </form>
        <form method="POST" action="{{ route('office.requests.refuse', $rentalRequest) }}" class="mt-3">
            @csrf
            <input class="field" name="decision_note" placeholder="Motif du refus (facultatif)">
            <button class="btn btn-ghost mt-2 w-full">Refuser</button>
        </form>
    @endif
@endsection
