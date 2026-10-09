@extends('layouts.shell')
@section('content')
    <x-page-header title="Maintenance" subtitle="Incidents ouverts et interventions">
        <x-slot:actions>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createMaintenance"><i class="bi bi-plus-lg" aria-hidden="true"></i> Déclarer une maintenance</button>
        </x-slot:actions>
    </x-page-header>
    <div class="modal fade" id="createMaintenance" tabindex="-1" aria-labelledby="createMaintenanceLabel" @if ($errors->any()) data-open-on-load @endif>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form method="POST" action="{{ route('office.maintenance.store') }}" enctype="multipart/form-data" class="modal-content">
                @csrf
                <div class="modal-header"><h2 class="modal-title h5" id="createMaintenanceLabel">Déclarer une maintenance</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body d-grid gap-2">
                    <select class="field" name="unit_id" required>@foreach ($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select>
                    <input class="field" name="title" placeholder="Titre" required>
                    <textarea class="field" name="description" placeholder="Description" required></textarea>
                    <select class="field" name="urgency"><option value="low">Faible</option><option value="normal" selected>Normale</option><option value="high">Urgente</option></select>
                    <input class="field" type="file" name="photo" accept="image/*">
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary">Ouvrir</button></div>
            </form>
        </div>
    </div>
    <div class="mt-4 space-y-2">
        @foreach ($items as $item)
            <a class="card block" href="{{ route('office.maintenance.show', $item) }}">
                <div class="flex justify-between gap-2"><p class="font-semibold">{{ $item->title }}</p><x-badge :tone="$item->status->tone()">{{ $item->status->label() }}</x-badge></div>
                <p class="text-sm text-muted">{{ $item->unit->name }} · {{ $item->tenant?->name }}</p>
            </a>
        @endforeach
    </div>
@endsection
