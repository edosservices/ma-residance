@props(['tone' => 'neutral'])
@php
    $tones = [
        'neutral' => 'bg-sand text-ink',
        'good' => 'bg-emerald-100 text-emerald-900',
        'warn' => 'bg-amber-100 text-amber-950',
        'bad' => 'bg-red-100 text-red-900',
        'info' => 'bg-sky-100 text-sky-950',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold '.($tones[$tone] ?? $tones['neutral'])]) }}>{{ $slot }}</span>
