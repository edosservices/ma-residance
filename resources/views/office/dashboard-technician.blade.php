@extends('layouts.shell')
@section('content')
    <x-page-header title="Interventions" subtitle="Incidents signalés sur douze mois">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('office.maintenance.index') }}">Voir les incidents</a>
        </x-slot:actions>
    </x-page-header>
    <x-chart :series="$work_series" left="Incidents signalés" :show-right="false" :show-net="false" :as-money="false" />
    <p class="small text-muted mt-2 mb-4">Chaque barre compte les demandes créées dans le mois.</p>
    <div class="mt-2">
        @forelse ($maintenance as $item)
            <a class="card d-block mb-2" href="{{ route('office.maintenance.show', $item) }}">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <p class="font-semibold mb-0">{{ $item->title }}</p>
                    <x-badge :tone="$item->urgency->tone()">{{ $item->urgency->label() }}</x-badge>
                </div>
                <p class="text-muted mb-0">{{ $item->unit->name }} · {{ $item->status->label() }}</p>
            </a>
        @empty
            <p class="card text-muted mb-0">Rien en cours.</p>
        @endforelse
    </div>
@endsection
