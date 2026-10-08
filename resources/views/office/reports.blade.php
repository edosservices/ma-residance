@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Performance</h1>
    <form method="GET" class="mt-3">
        <select class="field" name="propriete" onchange="this.form.submit()">
            <option value="">Toutes les propriétés</option>
            @foreach ($properties as $property)
                <option value="{{ $property->id }}" @selected((int) $propertyId === (int) $property->id)>{{ $property->name }}</option>
            @endforeach
        </select>
        <input type="hidden" name="periode" value="{{ $period }}">
    </form>
    @include('partials.period', ['period' => $period])
    <p class="mb-3 text-sm text-muted">{{ $label }} · occupation {{ $stock['occupancy_rate'] }} % · {{ $stock['free'] }} libres / {{ $stock['occupied'] }} occupés</p>
    @php
        $max = max(1, collect($series)->max(fn ($month) => max($month['collected'], $month['expenses'])));
    @endphp
    <div class="card">
        <p class="text-sm font-semibold">12 mois · {{ $currency }}</p>
        <p class="text-xs text-muted">Barre pleine : encaissé. Barre claire : dépenses.</p>
        <div class="mt-3 flex items-end gap-1">
            @foreach ($series as $month)
                @php
                    $collectedPx = $month['collected'] > 0 ? max(6, (int) round($month['collected'] / $max * 96)) : 0;
                    $expensePx = $month['expenses'] > 0 ? max(6, (int) round($month['expenses'] / $max * 96)) : 0;
                @endphp
                <div class="flex flex-1 flex-col items-center">
                    <div class="flex w-full items-end justify-center gap-0.5" style="height: 6.5rem">
                        <div class="w-1/2 rounded-t bg-brand" style="height: {{ $collectedPx }}px"></div>
                        <div class="w-1/2 rounded-t bg-sand" style="height: {{ $expensePx }}px"></div>
                    </div>
                    <span class="mt-1 text-[10px] text-muted">{{ $month['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
    <div class="mt-4 space-y-2">
        @foreach ($financials as $code => $money)
            <article class="card">
                <p class="font-semibold">{{ $label }} · {{ $code }}</p>
                <p class="mt-1 text-sm">Attendu {{ money($money['expected'], $code) }}</p>
                <p class="text-sm">Encaissé {{ money($money['collected'], $code) }} · Dépenses {{ money($money['expenses'], $code) }}</p>
                <p class="text-lg font-semibold">Net {{ money($money['net'], $code) }}</p>
                <p class="text-sm text-muted">{{ $money['collection_rate'] }} % des factures dues sont soldées · reste {{ money($money['outstanding'], $code) }}</p>
            </article>
        @endforeach
        @if ($financials === [])
            <p class="card text-sm text-muted">Rien sur cette période. L'historique des autres mois n'est pas effacé.</p>
        @endif
    </div>
    <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-muted">Cumul</h2>
    <div class="mt-2 space-y-2">
        @foreach (['Cette année' => $year, 'Depuis le début' => $lifetime] as $title => $bucket)
            @foreach ($bucket as $code => $money)
                <article class="card">
                    <p class="text-sm text-muted">{{ $title }}</p>
                    <p class="font-semibold">{{ $code }}</p>
                    <p class="text-sm">Revenus {{ money($money['collected'], $code) }} · Dépenses {{ money($money['expenses'], $code) }}</p>
                    <p class="text-lg font-semibold">Net {{ money($money['net'], $code) }}</p>
                </article>
            @endforeach
        @endforeach
    </div>
    <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-muted">Par propriété</h2>
    <div class="mt-2 space-y-2">
        @foreach ($breakdown as $row)
            <article class="card">
                <p class="font-semibold">{{ $row['name'] }}</p>
                @forelse ($row['currencies'] as $code => $money)
                    <p class="mt-1 text-sm">{{ $code }} · revenus {{ money($money['collected'], $code) }} · dépenses {{ money($money['expenses'], $code) }}</p>
                    <p class="text-sm font-semibold">Net {{ money($money['net'], $code) }}</p>
                @empty
                    <p class="text-sm text-muted">Aucun mouvement sur la période.</p>
                @endforelse
            </article>
        @endforeach
    </div>
    <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-muted">Dépenses par catégorie</h2>
    @foreach ($categories as $row)
        <p class="mt-2 text-sm">{{ $row->category->name }} · {{ money((int) $row->total, $row->currency) }}</p>
    @endforeach
@endsection
