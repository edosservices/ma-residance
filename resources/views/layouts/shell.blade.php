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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="theme-{{ $shellTheme }}">
    <div class="app-shell">
        <aside class="app-sidebar d-none d-lg-flex flex-column">
            <x-logo :href="$home ?? route('home')" class="mb-3" />
            @isset($shellNav)
                @include($shellNav)
            @endisset
        </aside>
        <div class="app-main">
            <header class="app-topbar">
                <button class="icon-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appNav" aria-controls="appNav" aria-label="Ouvrir le menu">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <div class="d-lg-none brand-top">
                    <x-logo :href="$home ?? route('home')" tagline="" />
                </div>
                <div class="topbar-copy d-none d-lg-block">
                    <strong>{{ $currentOrganization->name ?? 'Ma Résidence' }}</strong>
                    <span>{{ $eyebrow ?? '' }}</span>
                </div>
                <div class="ms-auto d-flex align-items-center gap-2">
                    @if ($currentUser)
                        <x-notifications />
                        <div class="dropdown">
                            <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-label="Compte de {{ $currentUser->name }}">
                                <i class="bi bi-person" aria-hidden="true"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <div class="px-3 py-2">
                                    <strong class="d-block">{{ $currentUser->name }}</strong>
                                    <span class="small text-muted">{{ $eyebrow ?? $currentUser->phone }}</span>
                                </div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Se déconnecter</button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </header>
            <main class="app-content">
                <x-flash />
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
        </div>
    </div>
    @include('partials.quickbar')
</body>
</html>
