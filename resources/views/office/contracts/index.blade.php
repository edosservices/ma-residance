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
        <div class="modal fade" id="createContract" tabindex="-1" aria-labelledby="createContractLabel" @if ($errors->any()) data-open-on-load @endif>
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form method="POST" action="{{ route('office.contracts.store') }}" class="modal-content">
                @csrf
                <div class="modal-header"><h2 class="modal-title h5" id="createContractLabel">Nouveau contrat</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body d-grid gap-2">
                <select class="field" name="tenant_id" required>
                    <option value="">Locataire</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                    @endforeach
                </select>
                <select class="field" name="unit_id" required>
                    <option value="">Logement libre</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }} · {{ money($unit->price_minor, $unit->currency) }}</option>
                    @endforeach
                </select>
                <input class="field" type="date" name="start_date" value="{{ now()->toDateString() }}" required>
                <input class="field" type="date" name="end_date">
                <input class="field" name="rent" placeholder="Loyer" required>
                <select class="field" name="currency">
                    @foreach ($currencies as $currency)
                        <option>{{ $currency }}</option>
                    @endforeach
                </select>
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
