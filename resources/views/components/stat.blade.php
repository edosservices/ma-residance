@props(['label', 'value', 'hint' => null])
<div {{ $attributes->merge(['class' => 'card']) }}>
    <p class="text-[11px] font-semibold uppercase tracking-wider text-muted">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold tracking-tight">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-sm text-muted">{{ $hint }}</p>
    @endif
</div>
