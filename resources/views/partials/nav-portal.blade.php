@php
    $links = [
        ['portal.dashboard', route('portal.dashboard'), 'Accueil', 'house'],
        ['portal.invoices.*', route('portal.invoices.index'), 'Mon solde', 'wallet2'],
        ['portal.contract', route('portal.contract'), 'Mes documents', 'file-earmark-text'],
        ['portal.maintenance.*', route('portal.maintenance.index'), 'Mes demandes', 'tools'],
        ['portal.infos', route('portal.dashboard').'#infos', 'Mes infos utiles', 'person-lines-fill'],
        ['portal.notifications*', route('portal.notifications.index'), 'Actualités', 'bell'],
        ['portal.messages.*', route('portal.messages.index'), 'Aide', 'question-circle'],
        ['portal.moveout.*', route('portal.moveout.create'), 'Départ', 'house-dash'],
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
