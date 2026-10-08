@props(['icon' => 'inbox', 'title' => 'Rien à afficher', 'text' => null])
<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <i class="bi bi-{{ $icon }} fs-4" aria-hidden="true"></i>
    <p class="fw-semibold text-ink mb-1 mt-2">{{ $title }}</p>
    @if ($text)<p class="mb-0">{{ $text }}</p>@endif
    @if (trim($slot))<div class="mt-3">{{ $slot }}</div>@endif
</div>
