@props(['label', 'value', 'hint' => null, 'href' => null, 'icon' => null, 'tone' => 'green'])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'stat-card stat-'.$tone]) }}>
    @if ($icon)<i class="bi bi-{{ $icon }} stat-icon" aria-hidden="true"></i>@endif
    <span class="label">{{ $label }}</span>
    <span class="value">{{ $value }}</span>
    @if ($hint)<span class="hint">{{ $hint }}</span>@endif
</{{ $tag }}>
