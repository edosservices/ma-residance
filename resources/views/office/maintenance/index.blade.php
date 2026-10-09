@extends('layouts.shell')
@section('content')
    <x-page-header title="Maintenance" subtitle="Chaque déclaration garde qui l'a ouverte, pour quel logement, et son urgence.">
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
                    <p class="text-muted mb-0">Déclarant : {{ auth()->user()->name }}. La date et le déclarant sont enregistrés avec l'incident.</p>
                    <div class="field-group">
                        <label for="maintenance-unit">Logement</label>
                        <select id="maintenance-unit" class="field" name="unit_id" required>
                            <option value="">Choisir le logement, ex. Appartement A</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-group">
                        <label for="maintenance-title">Titre</label>
                        <input id="maintenance-title" class="field" name="title" placeholder="Ex. Fuite sous l'évier" required>
                    </div>
                    <div class="field-group">
                        <label for="maintenance-description">Description</label>
                        <textarea id="maintenance-description" class="field" name="description" placeholder="Ex. L'eau coule depuis ce matin dans la cuisine." required></textarea>
                    </div>
                    <div class="field-group">
                        <label for="maintenance-urgency">Urgence</label>
                        <select id="maintenance-urgency" class="field" name="urgency">
                            <option value="low">Faible</option>
                            <option value="normal" selected>Normale</option>
                            <option value="high">Urgente</option>
                        </select>
                        <span class="field-hint">Urgente : le bailleur et le gérant voient une alerte à chaque visite, jusqu'à la solution.</span>
                    </div>
                    <div class="field-group">
                        <label for="maintenance-photo">Photo</label>
                        <input id="maintenance-photo" class="field" type="file" name="photo" accept="image/*">
                        <span class="field-hint">Ex. photo de la fuite.</span>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary">Ouvrir</button></div>
            </form>
        </div>
    </div>
    <div class="mt-4 space-y-2">
        @foreach ($items as $item)
            <a class="card block" href="{{ route('office.maintenance.show', $item) }}">
                <div class="flex justify-between gap-2">
                    <p class="font-semibold">{{ $item->title }}</p>
                    <x-badge :tone="$item->urgency->value === 'high' && $item->handled_at === null ? 'bad' : $item->status->tone()">{{ $item->urgency->value === 'high' && $item->handled_at === null ? 'Urgente' : $item->status->label() }}</x-badge>
                </div>
                <p class="text-sm text-muted mb-0">{{ $item->unit->name }} · {{ $item->tenant?->name ?? 'Sans locataire' }} · {{ $item->urgency->label() }}</p>
                @if (sees_trace())
                    <p class="small text-muted mb-0">Déclarée le {{ $item->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }} par {{ $item->reporter?->name ?? 'Inconnu' }}</p>
                @endif
            </a>
        @endforeach
    </div>
@endsection
