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
            <div class="row g-4 g-lg-5">
                <div class="col-lg-5">
                    <img class="edos-logo" src="{{ asset('images/edos-services-logo.png') }}" alt="EDOS SERVICES" width="2000" height="667">
                    <p class="mt-3 mb-2 fw-semibold text-white">EDOS SERVICES</p>
                    <p class="mb-0">Solutions numériques et logiciels de gestion conçus pour les entreprises.</p>
                </div>
                <div class="col-md-6 col-lg-3">
                    <p class="kicker">Ma Résidence</p>
                    <x-logo href="{{ route('home') }}" tone="light" class="mb-3" />
                    <p class="mb-0">Plateforme de gestion immobilière pour les biens, les locataires, les contrats, les loyers et le suivi financier.</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <p class="kicker">Accès</p>
                    <div class="d-grid gap-2 mb-3">
                        <a href="{{ route('home') }}"><i class="bi bi-house me-2" aria-hidden="true"></i>Accueil</a>
                        <a href="{{ route('home') }}#fonctionnalites"><i class="bi bi-grid me-2" aria-hidden="true"></i>Fonctionnalités</a>
                        <a href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Connexion</a>
                        <a href="{{ route('register.landlord') }}"><i class="bi bi-building-add me-2" aria-hidden="true"></i>Inscription bailleur</a>
                        <a href="{{ route('register.tenant') }}"><i class="bi bi-person-add me-2" aria-hidden="true"></i>Inscription locataire</a>
                    </div>
                    <a class="btn btn-light" href="tel:+243992749668">
                        <i class="bi bi-telephone" aria-hidden="true"></i>
                        <span>+243 992 749 668</span>
                    </a>
                </div>
            </div>
            <hr class="footer-rule">
            <div class="d-flex flex-wrap justify-content-between gap-2 small">
                <p class="mb-0">© {{ now()->year }} EDOS SERVICES</p>
                <p class="mb-0">Photographies : Unsplash</p>
            </div>
        </div>
    </footer>
</body>
</html>
