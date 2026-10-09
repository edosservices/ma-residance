@props(['series' => [], 'currency' => ''])
@php
    $rows = collect($series);
    $max = max(1, (int) $rows->max(fn ($month) => max($month['collected'], $month['expenses'])));
    $netMax = max(1, (int) $rows->max(fn ($month) => abs($month['net'])));
@endphp
<div class="chart-card">
    <div class="d-flex flex-wrap justify-content-between gap-2">
        <strong>12 mois · {{ $currency }}</strong>
        <div class="legend">
            <span><i class="dot dot-green"></i>Encaissé</span>
            <span><i class="dot dot-sand"></i>Dépenses</span>
        </div>
    </div>
    <div class="chart-bars" role="img" aria-label="Encaissements et dépenses des douze derniers mois">
        @foreach ($rows as $month)
            @php
                $collectedPx = $month['collected'] > 0 ? max(6, (int) round($month['collected'] / $max * 112)) : 0;
                $expensePx = $month['expenses'] > 0 ? max(6, (int) round($month['expenses'] / $max * 112)) : 0;
            @endphp
            <div class="chart-col" title="{{ $month['label'] }} · encaissé {{ money($month['collected'], $currency) }} · dépenses {{ money($month['expenses'], $currency) }}">
                <div class="chart-pair">
                    <span class="bar-in" style="height: {{ $collectedPx }}px"></span>
                    <span class="bar-out" style="height: {{ $expensePx }}px"></span>
                </div>
                <small>{{ $month['label'] }}</small>
            </div>
        @endforeach
    </div>
    <p class="small text-muted mt-3 mb-2">Évolution du net</p>
    <div class="chart-bars" style="height: 92px" role="img" aria-label="Net des douze derniers mois">
        @foreach ($rows as $month)
            @php $netPx = $month['net'] != 0 ? max(6, (int) round(abs($month['net']) / $netMax * 64)) : 0; @endphp
            <div class="chart-col" title="{{ $month['label'] }} · net {{ money($month['net'], $currency) }}">
                <div class="chart-pair">
                    <span class="bar-net {{ $month['net'] < 0 ? 'negative' : '' }}" style="height: {{ $netPx }}px; width: 70%"></span>
                </div>
            </div>
        @endforeach
    </div>
    <table class="visually-hidden">
        <caption>Séries mensuelles {{ $currency }}</caption>
        <thead><tr><th>Mois</th><th>Encaissé</th><th>Dépenses</th><th>Net</th></tr></thead>
        <tbody>
            @foreach ($rows as $month)
                <tr>
                    <td>{{ $month['label'] }}</td>
                    <td>{{ money($month['collected'], $currency) }}</td>
                    <td>{{ money($month['expenses'], $currency) }}</td>
                    <td>{{ money($month['net'], $currency) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
