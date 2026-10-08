@php
    $current = $period ?? request('periode', 'month');
    $choices = ['month' => 'Ce mois', 'prev_month' => 'Mois précédent', 'year' => 'Cette année', 'prev_year' => 'Année précédente', 'all' => 'Depuis le début'];
@endphp
<div class="period-pills" role="tablist" aria-label="Période">
    @foreach ($choices as $key => $label)
        <a href="{{ request()->fullUrlWithQuery(['periode' => $key]) }}" class="{{ $current === $key ? 'is-active' : '' }}">{{ $label }}</a>
    @endforeach
</div>
