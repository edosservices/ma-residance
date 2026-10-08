@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">{{ $remittance->reference }}</h1>
    <p class="mt-2 text-3xl font-semibold">{{ money($remittance->amount_minor, $remittance->currency) }}</p>
    <p class="text-sm text-muted">{{ $remittance->agent->name }} · {{ $remittance->status->label() }}</p>
    <div class="mt-4 space-y-2">
        @foreach ($remittance->items as $item)
            <div class="card text-sm">
                <p class="font-semibold">{{ $item->collection->tenant->name }}</p>
                <p>{{ $item->collection->code }} · {{ money($item->amount_minor, $remittance->currency) }}</p>
            </div>
        @endforeach
    </div>
    @if ($remittance->status->value === 'pending' && allows('remittances.confirm') && (int) $remittance->agent_id !== (int) auth()->id())
        <form method="POST" action="{{ route('office.remittances.confirm', $remittance) }}" class="mt-4">
            @csrf
            <button class="btn btn-primary w-full">J'ai reçu les fonds</button>
        </form>
        <form method="POST" action="{{ route('office.remittances.reject', $remittance) }}" class="mt-2 space-y-2">
            @csrf
            <input class="field" name="reason" placeholder="Motif" required>
            <button class="btn btn-ghost w-full">Refuser</button>
        </form>
    @endif
@endsection
