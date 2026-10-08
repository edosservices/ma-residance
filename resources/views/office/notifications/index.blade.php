@extends('layouts.shell')
@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Notifications</h1>
        <form method="POST" action="{{ route('office.notifications.read') }}">@csrf<button class="text-sm font-semibold text-brand">Tout lire</button></form>
    </div>
    @if (allows('notifications.send'))
        <form method="POST" action="{{ route('office.notifications.store') }}" class="card mt-4 space-y-2">
            @csrf
            <select class="field" name="audience">
                <option value="all">Tous les locataires</option>
                <option value="one">Un locataire</option>
                <option value="several">Plusieurs locataires</option>
                <option value="property">Une propriété</option>
                <option value="unit">Un logement</option>
            </select>
            <select class="field" name="tenant_id"><option value="">Un locataire</option>@foreach ($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->name }}</option>@endforeach</select>
            <select class="field" name="tenant_ids[]" multiple size="4">
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                @endforeach
            </select>
            <select class="field" name="property_id"><option value="">Propriété</option>@foreach ($properties as $property)<option value="{{ $property->id }}">{{ $property->name }}</option>@endforeach</select>
            <select class="field" name="unit_id"><option value="">Logement</option>@foreach ($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select>
            <input class="field" name="title" placeholder="Titre" required>
            <textarea class="field" name="body" placeholder="Message" required></textarea>
            <button class="btn btn-primary w-full">Envoyer</button>
        </form>
    @endif
    <div class="mt-4 space-y-2">
        @foreach ($items as $item)
            <a class="card block {{ $item->read_at ? 'opacity-70' : '' }}" href="{{ $item->data['url'] ?? '#' }}">
                <p class="font-semibold">{{ $item->data['title'] ?? 'Notification' }}</p>
                <p class="text-sm text-muted">{{ $item->data['body'] ?? '' }}</p>
            </a>
        @endforeach
    </div>
@endsection
