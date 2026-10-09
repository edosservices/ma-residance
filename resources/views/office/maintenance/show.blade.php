@extends('layouts.shell')
@section('content')
    <div class="flex items-start justify-between gap-2">
        <h1 class="text-2xl font-semibold">{{ $item->title }}</h1>
        <x-badge :tone="$item->status->tone()">{{ $item->status->label() }}</x-badge>
    </div>
    <p class="mt-2 text-sm text-muted">{{ $item->unit->name }}@if ($item->tenant) · {{ $item->tenant->name }}@endif · {{ $item->urgency->label() }}</p>
    <p class="card mt-3 text-sm">{{ $item->description }}</p>
    @if (sees_trace())
        @include('partials.maintenance-trace', ['item' => $item])
    @else
        <p class="text-muted mt-3">Le détail de cette déclaration reste chez le bailleur.</p>
    @endif
    @php $officeMember = app(\App\Support\CurrentContext::class)->member(); @endphp
    @if ($item->urgency->value === 'high' && $item->handled_at === null && ! in_array($item->status->value, ['done', 'cancelled'], true) && in_array($officeMember?->role->value, ['owner', 'manager'], true))
        <form method="POST" action="{{ route('office.maintenance.accept', $item) }}" class="card mt-3">
            @csrf
            <p class="fw-semibold">Accepter que c'est bien géré</p>
            <p class="text-muted">L'alerte disparaît chez le bailleur et le gérant. L'incident reste dans l'historique.</p>
            <input class="field" name="note" maxlength="500" placeholder="Ex. Fuite réparée, le locataire a confirmé.">
            <button class="btn btn-primary w-100 mt-2">C'est bien géré</button>
        </form>
    @endif
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
            <input class="field" name="estimated_cost" placeholder="Ex. 25.00 si devis">
            <input class="field" name="actual_cost" placeholder="Ex. 25.00 si terminé">
            <input class="field" name="currency" value="{{ $item->currency ?? 'USD' }}" maxlength="3">
            <input class="field" name="payee" placeholder="Ex. Paul, technicien">
            <textarea class="field" name="note" placeholder="Ex. Pièce remplacée, fuite arrêtée."></textarea>
            <input class="field" type="file" name="photo" accept="image/*">
            <button class="btn btn-primary w-full">Mettre à jour</button>
        </form>
    @endif
@endsection
