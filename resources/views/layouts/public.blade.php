<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f6b43">
    <meta name="description" content="Ma Résidence centralise propriétés, locataires, contrats, loyers, dépenses et suivi financier pour chaque bailleur.">
    <title>@yield('document-title', 'Ma Résidence')</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="marketing-nav">
        <div class="container py-2 d-flex align-items-center gap-2">
            <x-logo href="{{ route('home') }}" />
            <button class="icon-btn d-lg-none ms-auto" type="button" data-bs-toggle="offcanvas" data-bs-target="#publicNav" aria-label="Ouvrir le menu">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <nav class="d-none d-lg-flex align-items-center gap-3 ms-auto" aria-label="Public">
                <a class="fw-semibold text-decoration-none text-ink" href="{{ route('home') }}#plateforme">La plateforme</a>
                <a class="fw-semibold text-decoration-none text-ink" href="{{ route('home') }}#fonctionnalites">Fonctionnalités</a>
                <a class="fw-semibold text-decoration-none text-ink" href="{{ route('catalog.index') }}">Logements</a>
                @auth
                    <a class="btn btn-primary" href="{{ route('home') }}">Mon espace</a>
                @else
                    <a class="fw-semibold text-decoration-none text-ink" href="{{ route('login') }}">Connexion</a>
                    <a class="btn btn-primary" href="{{ route('register.landlord') }}">Commencer maintenant</a>
                @endauth
            </nav>
        </div>
    </header>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="publicNav" aria-labelledby="publicNavLabel">
        <div class="offcanvas-header">
            <h2 class="h5 mb-0" id="publicNavLabel">Ma Résidence</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
        </div>
        <div class="offcanvas-body d-grid gap-2">
            <a class="btn btn-ghost" href="{{ route('home') }}#plateforme">La plateforme</a>
            <a class="btn btn-ghost" href="{{ route('home') }}#fonctionnalites">Fonctionnalités</a>
            <a class="btn btn-ghost" href="{{ route('home') }}#biens">Biens et unités</a>
            <a class="btn btn-ghost" href="{{ route('home') }}#loyers">Loyers et paiements</a>
            <a class="btn btn-ghost" href="{{ route('catalog.index') }}">Logements libres</a>
            <a class="btn btn-ghost" href="{{ route('login') }}">Connexion</a>
            <a class="btn btn-primary" href="{{ route('register.landlord') }}">Commencer maintenant</a>
            <a class="btn btn-ghost" href="{{ route('register.tenant') }}">Je suis locataire</a>
        </div>
    </div>
    <main class="public-main">
        @yield('content')
    </main>
    <footer class="site-footer">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-lg-5">
                    <x-logo href="{{ route('home') }}" />
                    <p class="mt-3 mb-0 text-muted">Chaque bailleur dispose de son espace. Chaque locataire ne voit que son logement.</p>
                </div>
                <div class="col-6 col-lg-3">
                    <p class="kicker">Plateforme</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('home') }}#fonctionnalites">Fonctionnalités</a>
                        <a href="{{ route('home') }}#pilotage">Indicateurs</a>
                        <a href="{{ route('catalog.index') }}">Logements</a>
                    </div>
                </div>
                <div class="col-6 col-lg-4">
                    <p class="kicker">Accès</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('login') }}">Connexion</a>
                        <a href="{{ route('register.landlord') }}">Inscription bailleur</a>
                        <a href="{{ route('register.tenant') }}">Inscription locataire</a>
                    </div>
                </div>
            </div>
            <p class="small text-muted mt-4 mb-0">Ma Résidence · Photographies : Unsplash</p>
        </div>
    </footer>
</body>
</html>
