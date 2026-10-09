@extends('layouts.shell')
@section('content')
    <x-page-header title="Performance" subtitle="{{ $label }} · occupation {{ $stock['occupancy_rate'] }} % · {{ $stock['free'] }} libres / {{ $stock['occupied'] }} occupés" />
    <form method="GET" class="row g-2 mb-3">
        <div class="col-12 col-md-6">
            <select class="field" name="propriete" onchange="this.form.submit()" aria-label="Propriété">
                <option value="">Toutes les propriétés</option>
                @foreach ($properties as $property)
                    <option value="{{ $property->id }}" @selected((int) $propertyId === (int) $property->id)>{{ $property->name }}</option>
                @endforeach
            </select>
        </div>
        <input type="hidden" name="periode" value="{{ $period }}">
    </form>
    @include('partials.period', ['period' => $period])
    <x-chart :series="$series" :currency="$currency" />
    <div class="row g-3 mt-1">
        @forelse ($financials as $code => $money)
            <div class="col-md-6">
                <article class="card h-100">
                    <p class="fw-semibold">{{ $label }} · {{ $code }}</p>
                    <p class="mb-1">Attendu {{ money($money['expected'], $code) }}</p>
                    <p class="mb-1">Encaissé {{ money($money['collected'], $code) }} · Dépenses {{ money($money['expenses'], $code) }}</p>
                    <p class="h4 mb-1">Net {{ money($money['net'], $code) }}</p>
                    <p class="small text-muted mb-0">{{ $money['collection_rate'] }} % des factures dues sont soldées · reste {{ money($money['outstanding'], $code) }}</p>
                </article>
            </div>
        @empty
            <div class="col-12"><x-empty title="Rien sur cette période" text="L'historique des autres mois n'est pas effacé." /></div>
        @endforelse
    </div>
    <h2 class="h6 text-uppercase text-muted mt-4">Cumul</h2>
    <div class="row g-3">
        @foreach (['Cette année' => $year, 'Depuis le début' => $lifetime] as $title => $bucket)
            @foreach ($bucket as $code => $money)
                <div class="col-md-6">
                    <article class="card h-100">
                        <p class="small text-muted mb-1">{{ $title }}</p>
                        <p class="fw-semibold">{{ $code }}</p>
                        <p class="mb-1">Revenus {{ money($money['collected'], $code) }} · Dépenses {{ money($money['expenses'], $code) }}</p>
                        <p class="h5 mb-0">Net {{ money($money['net'], $code) }}</p>
                    </article>
                </div>
            @endforeach
        @endforeach
    </div>
    <h2 class="h6 text-uppercase text-muted mt-4">Par propriété</h2>
    <div class="row g-3">
        @foreach ($breakdown as $row)
            <div class="col-md-6">
                <article class="card h-100">
                    <p class="fw-semibold">{{ $row['name'] }}</p>
                    @forelse ($row['currencies'] as $code => $money)
                        <p class="mb-1">{{ $code }} · revenus {{ money($money['collected'], $code) }} · dépenses {{ money($money['expenses'], $code) }}</p>
                        <p class="fw-semibold">Net {{ money($money['net'], $code) }}</p>
                    @empty
                        <p class="text-muted mb-0">Aucun mouvement sur la période.</p>
                    @endforelse
                </article>
            </div>
        @endforeach
    </div>
    <h2 class="h6 text-uppercase text-muted mt-4">Dépenses par catégorie</h2>
    @forelse ($categories as $row)
        <p class="mb-1">{{ $row->category->name }} · {{ money((int) $row->total, $row->currency) }}</p>
    @empty
        <p class="text-muted">Aucune dépense validée sur la période.</p>
    @endforelse
@endsection
