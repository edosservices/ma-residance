@props([
    'series' => [],
    'currency' => '',
    'left' => 'Encaissé',
    'right' => 'Dépenses',
    'showRight' => true,
    'showNet' => true,
    'asMoney' => true,
    'rightClass' => 'bar-out',
])
@php
    $rows = collect($series);
    $max = max(1, (int) $rows->max(fn ($month) => max($month['collected'], $showRight ? $month['expenses'] : 0)));
    $netMax = max(1, (int) $rows->max(fn ($month) => abs($month['net'])));
    $format = fn (int $amount) => $asMoney ? money($amount, $currency) : (string) $amount;
@endphp
<div class="chart-card">
    <div class="d-flex flex-wrap justify-content-between gap-2">
        <strong>12 mois @if ($currency !== '') · {{ $currency }} @endif</strong>
        <div class="legend">
            <span><i class="dot dot-green"></i>{{ $left }}</span>
            @if ($showRight)
                <span><i class="dot dot-sand"></i>{{ $right }}</span>
            @endif
        </div>
    </div>
    <div class="chart-bars" role="img" aria-label="{{ $left }}{{ $showRight ? ' et '.$right : '' }} des douze derniers mois">
        @foreach ($rows as $month)
            @php
                $collectedPx = $month['collected'] > 0 ? max(6, (int) round($month['collected'] / $max * 112)) : 0;
                $expensePx = $showRight && $month['expenses'] > 0 ? max(6, (int) round($month['expenses'] / $max * 112)) : 0;
            @endphp
            <div class="chart-col" title="{{ $month['label'] }} · {{ $left }} {{ $format($month['collected']) }}@if ($showRight) · {{ $right }} {{ $format($month['expenses']) }}@endif">
                <div class="chart-pair">
                    <span class="bar-in {{ $loop->last ? 'is-current' : '' }}" style="height: {{ $collectedPx }}px"></span>
                    @if ($showRight)
                        <span class="{{ $rightClass }}" style="height: {{ $expensePx }}px"></span>
                    @endif
                </div>
                <small>{{ $month['label'] }}</small>
            </div>
        @endforeach
    </div>
    @if ($showNet)
        <p class="small text-muted mt-3 mb-2">Évolution du net</p>
        <div class="chart-bars" style="height: 92px" role="img" aria-label="Net des douze derniers mois">
            @foreach ($rows as $month)
                @php $netPx = $month['net'] != 0 ? max(6, (int) round(abs($month['net']) / $netMax * 64)) : 0; @endphp
                <div class="chart-col" title="{{ $month['label'] }} · net {{ $format($month['net']) }}">
                    <div class="chart-pair">
                        <span class="bar-net {{ $month['net'] < 0 ? 'negative' : '' }}" style="height: {{ $netPx }}px; width: 70%"></span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    <table class="visually-hidden">
        <caption>Séries mensuelles {{ $currency }}</caption>
        <thead><tr><th>Mois</th><th>{{ $left }}</th>@if ($showRight)<th>{{ $right }}</th>@endif @if ($showNet)<th>Net</th>@endif</tr></thead>
        <tbody>
            @foreach ($rows as $month)
                <tr>
                    <td>{{ $month['label'] }}</td>
                    <td>{{ $format($month['collected']) }}</td>
                    @if ($showRight)<td>{{ $format($month['expenses']) }}</td>@endif
                    @if ($showNet)<td>{{ $format($month['net']) }}</td>@endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
