<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $shellTheme = $shellTheme ?? 'landlord';
        $themeColor = [
            'admin' => '#0c1b33',
            'tenant' => '#69468f',
            'collector' => '#0a5c60',
            'landlord' => '#0f6b43',
        ][$shellTheme] ?? '#0f6b43';
    @endphp
    <meta name="theme-color" content="{{ $themeColor }}">
    <title>@yield('document-title', 'Ma Résidence')</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.pwa')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="theme-{{ $shellTheme }}">
    <div class="app-shell">
        <aside class="app-sidebar d-none d-xl-flex flex-column">
            <x-logo :href="$home ?? route('home')" class="sidebar-brand" />
            <div class="app-nav-frame">
                <div class="app-nav-scroll">
                    @isset($shellNav)
                        @include($shellNav)
                    @endisset
                </div>
                <span class="app-nav-more" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
            </div>
            @if ($currentUser)
                @include('partials.account-menu')
            @endif
        </aside>
        <div class="app-main">
            <header class="app-topbar">
                <button class="icon-btn d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appNav" aria-controls="appNav" aria-label="Ouvrir le menu">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <div class="d-xl-none brand-top">
                    <x-logo :href="$home ?? route('home')" tagline="" />
                </div>
                <div class="topbar-copy d-none d-xl-block">
                    <strong>{{ $currentOrganization->name ?? 'Ma Résidence' }}</strong>
                    <span>{{ ($shellTheme ?? '') === 'tenant' ? ($portalContractLine ?? $eyebrow ?? '') : ($eyebrow ?? '') }}</span>
                </div>
                <div class="topbar-tools">
                    @if ($currentUser)
                        <x-notifications />
                        @if (($shellTheme ?? '') === 'tenant')
                            <div class="dropdown d-none d-xl-block">
                                <button class="logout-inline" type="button" data-bs-toggle="dropdown" aria-label="Gérer le compte de {{ $currentUser->name }}">Gérer mon compte</button>
                                <div class="dropdown-menu dropdown-menu-end account-dropdown">
                                    @include('partials.account-menu')
                                </div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="logout-inline-form">
                                @csrf
                                <button class="logout-inline" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Se déconnecter</button>
                            </form>
                        @else
                            <div class="dropdown">
                                <button class="account-trigger" type="button" data-bs-toggle="dropdown" aria-label="Compte de {{ $currentUser->name }}">
                                    <span class="account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($currentUser->name, 0, 1)) }}</span>
                                    <span class="account-copy d-none d-xl-grid">
                                        <strong>{{ $currentUser->name }}</strong>
                                        <small>{{ $eyebrow ?? 'Compte' }}</small>
                                    </span>
                                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end account-dropdown">
                                    @include('partials.account-menu')
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </header>
            @if (($shellTheme ?? '') === 'tenant')
                <nav class="portal-topnav d-none d-xl-block" aria-label="Espace locataire">
                    @isset($shellNav)
                        @include($shellNav)
                    @endisset
                </nav>
            @endif
            <main class="app-content">
                <x-flash />
                @include('partials.urgent-maintenance')
                @yield('content')
            </main>
        </div>
    </div>
    <div class="offcanvas offcanvas-start" tabindex="-1" id="appNav" aria-labelledby="appNavLabel">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title h5 mb-0" id="appNavLabel">Ma Résidence</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
        </div>
        <div class="offcanvas-body">
            <p class="text-muted small mb-2">{{ $currentOrganization->name ?? 'Plateforme' }} · {{ $eyebrow ?? '' }}</p>
            @isset($shellNav)
                @include($shellNav)
            @endisset
            @if ($currentUser)
                @include('partials.account-menu')
            @endif
        </div>
    </div>
    @include('partials.quickbar')
</body>
</html>
