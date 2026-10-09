@php
    $can = fn (string ...$permissions) => collect($permissions)->contains(fn ($permission) => allows($permission));
    $groups = [
        'Pilotage' => array_values(array_filter([
            ['office.dashboard', route('office.dashboard'), 'Tableau de bord', 'speedometer2', true],
            $can('reports.view', 'reports.financial') ? ['office.reports', route('office.reports'), 'Rapports', 'graph-up-arrow', false] : null,
        ])),
        'Patrimoine' => array_values(array_filter([
            $can('properties.manage', 'units.manage') ? ['office.properties.*', route('office.properties.index'), 'Résidences', 'buildings', false] : null,
            $can('properties.manage', 'units.manage') ? ['office.units.*', route('office.units.index'), 'Logements', 'door-open', false] : null,
        ])),
        'Occupation' => array_values(array_filter([
            $can('tenants.view', 'tenants.manage') ? ['office.tenants.*', route('office.tenants.index'), 'Locataires', 'people', false] : null,
            $can('contracts.view', 'contracts.manage') ? ['office.contracts.*', route('office.contracts.index'), 'Contrats', 'file-earmark-text', false] : null,
            $can('contracts.view', 'contracts.manage') ? ['office.requests.*', route('office.requests.index'), 'Demandes', 'inboxes', false] : null,
        ])),
        'Finances' => array_values(array_filter([
            $can('invoices.view', 'invoices.manage', 'payments.view') ? ['office.invoices.*', route('office.invoices.index'), 'Factures', 'receipt', false] : null,
            $can('payments.view', 'payments.validate', 'collections.record') ? ['office.payments.*', route('office.payments.index'), 'Paiements', 'cash-coin', false] : null,
            $can('expenses.view', 'expenses.manage') ? ['office.expenses.*', route('office.expenses.index'), 'Dépenses', 'wallet2', false] : null,
            $can('collections.record', 'collections.remit', 'remittances.confirm', 'payments.view') ? ['office.collections.*', route('office.collections.index'), 'Caisse', 'safe', false] : null,
            $can('collections.remit', 'remittances.confirm') ? ['office.remittances.*', route('office.remittances.index'), 'Remises', 'arrow-left-right', false] : null,
        ])),
        'Opérations' => array_values(array_filter([
            $can('maintenance.manage') ? ['office.maintenance.*', route('office.maintenance.index'), 'Maintenance', 'tools', false] : null,
            ['office.notifications.*', route('office.notifications.index'), 'Notifications', 'bell', false],
            $can('messages.use') ? ['office.messages.*', route('office.messages.index'), 'Messages', 'chat-dots', false] : null,
        ])),
        'Compte' => array_values(array_filter([
            $can('members.manage') ? ['office.members.*', route('office.members.index'), 'Collaborateurs', 'person-badge', false] : null,
            $can('settings.manage') ? ['office.settings.*', route('office.settings.edit'), 'Paramètres', 'gear', false] : null,
            $can('settings.manage', 'reports.financial') ? ['office.audit', route('office.audit'), 'Journal', 'journal-text', false] : null,
        ])),
    ];
@endphp
<nav class="app-nav" aria-label="Espace bailleur">
    @foreach ($groups as $label => $links)
        @if ($links !== [])
            <p class="app-nav-label">{{ $label }}</p>
            @foreach ($links as [$pattern, $url, $text, $icon])
                <a class="app-nav-link {{ request()->routeIs($pattern) ? 'is-active' : '' }}" href="{{ $url }}">
                    <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
                    <span>{{ $text }}</span>
                </a>
            @endforeach
        @endif
    @endforeach
</nav>
