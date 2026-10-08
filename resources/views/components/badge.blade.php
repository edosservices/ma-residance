@props(['tone' => 'neutral'])
@php
    $label = trim(preg_replace('/\s+/', ' ', strip_tags((string) $slot)) ?? '');
    $key = mb_strtolower($label);
    $visual = match (true) {
        $key === 'payé' || str_starts_with($key, 'payé') => 'paid',
        str_contains($key, 'retard') => 'late',
        str_contains($key, 'attente') || str_contains($key, 'partiel') || str_contains($key, 'signal') => 'pending',
        str_contains($key, 'cours') || str_contains($key, 'intervention') || str_contains($key, 'occup') || str_contains($key, 'vérif') || str_contains($key, 'confirm') => 'progress',
        str_contains($key, 'disponib') => 'free',
        str_contains($key, 'maintenance') => 'maintenance',
        default => $tone,
    };
    $icons = [
        'paid' => 'check-circle-fill',
        'good' => 'check-circle-fill',
        'pending' => 'hourglass-split',
        'warn' => 'hourglass-split',
        'late' => 'exclamation-circle-fill',
        'bad' => 'exclamation-circle-fill',
        'progress' => 'arrow-repeat',
        'info' => 'info-circle-fill',
        'free' => 'door-open',
        'neutral' => 'circle',
        'maintenance' => 'tools',
    ];
    $icon = $icons[$visual] ?? 'circle';
@endphp
<span {{ $attributes->merge(['class' => 'mr-badge mr-badge-'.$visual]) }} data-bs-toggle="tooltip" title="{{ $label }}">
    <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
    <span>{{ $slot }}</span>
</span>
