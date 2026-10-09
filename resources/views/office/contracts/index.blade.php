@extends('layouts.shell')
@section('content')
    <x-page-header title="Contrats" subtitle="Loyers, échéances et prorata">
        <x-slot:actions>
            @if (allows('contracts.manage'))
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createContract"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nouveau contrat</button>
            @endif
            <a class="btn btn-ghost" href="{{ route('office.requests.index') }}">Demandes</a>
        </x-slot:actions>
    </x-page-header>
    @if (allows('contracts.manage'))
        <div class="modal fade" id="createContract" tabindex="-1" aria-labelledby="createContractLabel" @if ($errors->any() && ! old('payee_name')) data-open-on-load @endif>
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <form method="POST" action="{{ route('office.contracts.store') }}" class="modal-content" enctype="multipart/form-data">
                @csrf
                <div class="modal-header"><h2 class="modal-title h5" id="createContractLabel">Nouveau contrat</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body d-grid gap-3">
                    <p class="mb-0 text-muted">Remplissez chaque champ à l'aide de l'exemple. La garantie en vigueur est <strong>{{ $depositMonths }} + {{ $advanceMonths }}</strong> : {{ $depositMonths }} mois de caution et {{ $advanceMonths }} mois d'avance. Pour changer cette formule, ouvrez les paramètres du bailleur.</p>
                    <div class="field-group">
                        <label for="tenant_id">Locataire</label>
                        <select class="field" id="tenant_id" name="tenant_id" required>
                            <option value="">Ex. Jean Dupont</option>
                            @foreach ($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected((string) old('tenant_id') === (string) $tenant->id)>{{ $tenant->name }}</option>
                            @endforeach
                        </select>
                        <span class="field-hint">Choisissez la personne qui occupera le logement. Ex. Jean Dupont.</span>
                    </div>
                    <div class="field-group">
                        <label for="unit_id">Logement</label>
                        <select class="field" id="unit_id" name="unit_id" required>
                            <option value="">Ex. appartement A kasindi · 240 USD</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string) old('unit_id') === (string) $unit->id)>{{ $unit->name }} · {{ money($unit->price_minor, $unit->currency) }}</option>
                            @endforeach
                        </select>
                        <span class="field-hint">Ex. appartement A kasindi · 240 USD. Seuls les logements libres apparaissent.</span>
                    </div>
                    <div class="field-group">
                        <label for="start_date">Début du bail</label>
                        <input class="field" id="start_date" type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}" required>
                        <span class="field-hint">Ex. 09 oct. 2026. C'est le premier jour où le locataire prend la maison.</span>
                    </div>
                    <div class="field-group">
                        <label for="end_date">Fin du bail</label>
                        <input class="field" id="end_date" type="date" name="end_date" value="{{ old('end_date') }}">
                        <span class="field-hint">Facultatif. Ex. 09 oct. 2027. Laissez vide si le bail n'a pas de date de fin.</span>
                    </div>
                    <div class="field-group">
                        <label for="rent">Loyer d'un mois</label>
                        <input class="field" id="rent" name="rent" value="{{ old('rent') }}" placeholder="Ex. 240.00" required>
                        <span class="field-hint">Ex. 240.00. Écrivez le loyer d'un mois, sans ajouter la garantie. La formule {{ $depositMonths }} + {{ $advanceMonths }} sera reprise sur l'acte de reconnaissance.</span>
                    </div>
                    <div class="field-group">
                        <label for="currency">Devise</label>
                        <select class="field" id="currency" name="currency">
                            @foreach ($currencies as $currency)
                                <option @selected(old('currency', $currencies[0] ?? '') === $currency)>{{ $currency }}</option>
                            @endforeach
                        </select>
                        <span class="field-hint">Ex. USD. Choisissez la devise dans laquelle le loyer est convenu.</span>
                    </div>
                    <div class="field-group">
                        <label for="conditions">Conditions</label>
                        <textarea class="field" id="conditions" name="conditions" placeholder="Ex. Paiement au plus tard le 5. L'entretien courant est à la charge du locataire.">{{ old('conditions') }}</textarea>
                        <span class="field-hint">Ex. Paiement au plus tard le 5. L'entretien courant est à la charge du locataire.</span>
                    </div>
                    <div class="field-group">
                        <label for="attachment">Pièce jointe</label>
                        <label class="field file-example" for="attachment">
                            <input id="attachment" type="file" name="attachment" accept="image/*,.pdf">
                            <span data-file-label>Ex. photo du contrat signé ou de la pièce d'identité</span>
                        </label>
                        <span class="field-hint">Facultatif. Ex. photo ou PDF du contrat signé, ou une image de la pièce d'identité.</span>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary">Créer</button></div>
            </form>
            </div>
        </div>
    @endif
    <div class="mt-4 space-y-2">
        @foreach ($contracts as $contract)
            <a class="card block" href="{{ route('office.contracts.show', $contract) }}">
                <div class="flex justify-between gap-2">
                    <p class="font-semibold">{{ $contract->reference }}</p>
                    <x-badge :tone="$contract->status->tone()">{{ $contract->status->label() }}</x-badge>
                </div>
                <p class="text-sm text-muted">{{ $contract->tenant->name }} · {{ $contract->unit->name }}</p>
                <p class="mt-1">{{ money($contract->rent_minor, $contract->currency) }}</p>
            </a>
        @endforeach
    </div>
@endsection
