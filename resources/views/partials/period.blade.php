@php
    $base = $base ?? url()->current();
    $current = $period ?? request('periode', 'month');
    $choices = ['month' => 'Ce mois', 'prev_month' => 'Mois précédent', 'year' => 'Cette année', 'prev_year' => 'Année précédente', 'all' => 'Depuis le début'];
@endphp
<div class="mb-4 flex gap-2 overflow-x-auto pb-1">
    @foreach ($choices as $key => $label)
        <a href="{{ request()->fullUrlWithQuery(['periode' => $key]) }}" class="shrink-0 rounded-full px-3 py-2 text-xs font-semibold {{ $current === $key ? 'bg-ink text-white' : 'bg-white text-muted' }}">{{ $label }}</a>
    @endforeach
</div>
