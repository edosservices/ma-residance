@props(['href' => null, 'compact' => false, 'tagline' => 'Gestion locative'])
<a href="{{ $href ?? ($home ?? route('home')) }}" {{ $attributes->merge(['class' => 'brand']) }}>
    <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 64 64" role="img">
            <rect width="64" height="64" rx="16" fill="#0f6b43"/>
            <path d="M14 30.5 32 16l18 14.5" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M20 29.5V46h24V29.5" fill="none" stroke="#fff" stroke-width="4" stroke-linejoin="round"/>
            <rect x="28" y="36" width="8" height="10" rx="1.5" fill="#f4eef8"/>
            <circle cx="46" cy="18" r="5" fill="#69468f"/>
        </svg>
    </span>
    @unless ($compact)
        <span class="brand-name">Ma Résidence<small>{{ $tagline }}</small></span>
    @endunless
</a>
