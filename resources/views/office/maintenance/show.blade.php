@extends('layouts.shell')
@section('content')
    <div class="flex items-start justify-between gap-2">
        <h1 class="text-2xl font-semibold">{{ $item->title }}</h1>
        <x-badge :tone="$item->status->tone()">{{ $item->status->label() }}</x-badge>
    </div>
    <p class="mt-2 text-sm text-muted">{{ $item->unit->name }} · {{ $item->urgency->label() }}</p>
    <p class="card mt-3 text-sm">{{ $item->description }}</p>
    @if ($item->estimated_cost_minor)
        <p class="mt-2 text-sm">Devis : {{ money($item->estimated_cost_minor, $item->currency) }}</p>
    @endif
    @if ($next !== [])
        <form method="POST" action="{{ route('office.maintenance.advance', $item) }}" enctype="multipart/form-data" class="card mt-4 space-y-2">
            @csrf
            <select class="field" name="status">
                @foreach ($next as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
            <select class="field" name="assigned_to"><option value="">Assigner</option>@foreach ($members as $member)<option value="{{ $member->user_id }}">{{ $member->user->name }}</option>@endforeach</select>
            <input class="field" name="estimated_cost" placeholder="Coût estimé si devis">
            <input class="field" name="actual_cost" placeholder="Coût réel si terminé">
            <input class="field" name="currency" value="{{ $item->currency ?? 'USD' }}" maxlength="3">
            <input class="field" name="payee" placeholder="Technicien">
            <textarea class="field" name="note" placeholder="Commentaire"></textarea>
            <input class="field" type="file" name="photo" accept="image/*">
            <button class="btn btn-primary w-full">Mettre à jour</button>
        </form>
    @endif
    <div class="mt-4 space-y-2">
        @foreach ($item->updates as $update)
            <p class="text-sm text-muted">{{ $update->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · {{ $update->user?->name }} · {{ $update->status_to }} {{ $update->note }}</p>
        @endforeach
    </div>
@endsection
