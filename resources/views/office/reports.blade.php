@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Rapports</h1>
    @include('partials.period', ['period' => $period])
    <p class="mb-3 text-sm text-muted">{{ $label }} · occupation {{ $stock['occupancy_rate'] }} % · {{ $stock['free'] }} libres / {{ $stock['occupied'] }} occupés</p>
    @php $max = max(1, collect($series)->max('collected')); @endphp
    <div class="card">
        <p class="text-sm font-semibold">Encaissé sur 12 mois · {{ $currency }}</p>
        <div class="mt-3 flex h-36 items-end gap-1">
            @foreach ($series as $month)
                <div class="flex h-full flex-1 flex-col items-center justify-end">
                    <div class="w-full rounded-t bg-brand" style="height: {{ max(4, (int) round($month['collected'] / $max * 100)) }}%"></div>
                    <span class="mt-1 text-[10px] text-muted">{{ $month['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
    <div class="mt-4 space-y-2">
        @foreach ($financials as $code => $money)
            <article class="card">
                <p class="font-semibold">{{ $code }}</p>
                <p class="text-sm">Attendu {{ money($money['expected'], $code) }} · Encaissé {{ money($money['collected'], $code) }}</p>
                <p class="text-sm">Dépenses {{ money($money['expenses'], $code) }} · Net {{ money($money['net'], $code) }}</p>
                <p class="text-sm text-muted">Recouvrement {{ $money['collection_rate'] }} % · Impayés {{ money($money['outstanding'], $code) }}</p>
            </article>
        @endforeach
    </div>
    <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-muted">Par propriété</h2>
    @foreach ($properties as $row)
        <p class="mt-2 text-sm">{{ $row['name'] }} · net {{ money($row['net'], $currency) }}</p>
    @endforeach
    <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-muted">Dépenses par catégorie</h2>
    @foreach ($categories as $row)
        <p class="mt-2 text-sm">{{ $row->category->name }} · {{ money((int) $row->total, $row->currency) }}</p>
    @endforeach
@endsection
