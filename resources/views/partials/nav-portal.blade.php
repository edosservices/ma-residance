@php
    $links = [
        ['portal.dashboard', route('portal.dashboard'), 'Tableau de bord', 'house'],
        ['portal.contract', route('portal.contract'), 'Mon contrat', 'file-earmark-text'],
        ['portal.invoices.*', route('portal.invoices.index'), 'Factures', 'receipt'],
        ['portal.maintenance.*', route('portal.maintenance.index'), 'Maintenance', 'tools'],
        ['portal.messages.*', route('portal.messages.index'), 'Messages', 'chat-dots'],
        ['portal.notifications*', route('portal.notifications.index'), 'Notifications', 'bell'],
        ['portal.moveout.*', route('portal.moveout.create'), 'Départ', 'box-arrow-right'],
    ];
@endphp
<nav class="app-nav" aria-label="Espace locataire">
    <p class="app-nav-label">Mon logement</p>
    @foreach ($links as [$pattern, $url, $text, $icon])
        <a class="app-nav-link {{ request()->routeIs($pattern) ? 'is-active' : '' }}" href="{{ $url }}">
            <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
            <span>{{ $text }}</span>
        </a>
    @endforeach
</nav>
