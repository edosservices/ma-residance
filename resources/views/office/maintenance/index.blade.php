@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Maintenance</h1>
    <form method="POST" action="{{ route('office.maintenance.store') }}" enctype="multipart/form-data" class="card mt-4 space-y-2">
        @csrf
        <select class="field" name="unit_id" required>@foreach ($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select>
        <input class="field" name="title" placeholder="Titre" required>
        <textarea class="field" name="description" placeholder="Description" required></textarea>
        <select class="field" name="urgency"><option value="low">Faible</option><option value="normal" selected>Normale</option><option value="high">Urgente</option></select>
        <input class="field" type="file" name="photo" accept="image/*">
        <button class="btn btn-primary w-full">Ouvrir</button>
    </form>
    <div class="mt-4 space-y-2">
        @foreach ($items as $item)
            <a class="card block" href="{{ route('office.maintenance.show', $item) }}">
                <div class="flex justify-between gap-2"><p class="font-semibold">{{ $item->title }}</p><x-badge :tone="$item->status->tone()">{{ $item->status->label() }}</x-badge></div>
                <p class="text-sm text-muted">{{ $item->unit->name }} · {{ $item->tenant?->name }}</p>
            </a>
        @endforeach
    </div>
@endsection
