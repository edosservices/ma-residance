@php
    $links = [
        [route('portal.dashboard'), 'Accueil', request()->routeIs('portal.dashboard')],
        [route('portal.invoices.index'), 'Factures', request()->routeIs('portal.invoices.*')],
        [route('portal.maintenance.index'), 'Incident', request()->routeIs('portal.maintenance.*')],
        [route('portal.messages.index'), 'Messages', request()->routeIs('portal.messages.*')],
        [route('portal.notifications.index'), 'Alertes', request()->routeIs('portal.notifications*')],
    ];
@endphp
<nav class="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-card/95 backdrop-blur">
    <div class="mx-auto flex max-w-3xl justify-around px-1 py-2">
        @foreach ($links as [$url, $label, $active])
            <a href="{{ $url }}" class="rounded-xl px-2 py-2 text-xs font-semibold {{ $active ? 'text-brand' : 'text-muted' }}">{{ $label }}</a>
        @endforeach
    </div>
</nav>
