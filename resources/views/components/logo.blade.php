@props(['href' => null, 'compact' => false, 'tagline' => 'Gestion locative', 'tone' => 'dark'])
<a href="{{ $href ?? ($home ?? route('home')) }}" {{ $attributes->merge(['class' => 'brand'.($tone === 'light' ? ' brand-light' : '')]) }}>
    <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 64 64" role="img">
            <rect width="64" height="64" rx="8" fill="#0F6B43"/>
            <rect x="12" y="14" width="40" height="3" fill="#fff"/>
            <rect x="15" y="22" width="8" height="22" rx="1" fill="#fff"/>
            <rect x="28" y="22" width="8" height="22" rx="1" fill="#F4EEF8"/>
            <rect x="41" y="22" width="8" height="22" rx="1" fill="#fff"/>
            <rect x="12" y="47" width="40" height="3" fill="#69468F"/>
        </svg>
    </span>
    @if (! $compact)
        <span class="brand-name">Ma Résidence
            @if ($tagline !== '')
                <small>{{ $tagline }}</small>
            @endif
        </span>
    @endif
</a>
