@php
    $links = [
        ['admin.dashboard', route('admin.dashboard'), 'Tableau de bord', 'speedometer2'],
        ['admin.organizations', route('admin.organizations'), 'Bailleurs', 'buildings'],
        ['admin.users', route('admin.users'), 'Comptes', 'person-lock'],
        ['admin.audit', route('admin.audit'), 'Activité', 'journal-text'],
        ['admin.settings', route('admin.settings'), 'Paramètres', 'gear'],
    ];
@endphp
<nav class="app-nav" aria-label="Super Admin">
    <p class="app-nav-label">Plateforme</p>
    @foreach ($links as [$pattern, $url, $text, $icon])
        <a class="app-nav-link {{ request()->routeIs($pattern) ? 'is-active' : '' }}" href="{{ $url }}">
            <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
            <span>{{ $text }}</span>
        </a>
    @endforeach
</nav>
