<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f6b43">
    <meta name="description" content="Ma Résidence centralise propriétés, locataires, contrats, loyers, dépenses et suivi financier pour chaque bailleur.">
    <title>@yield('document-title', 'Ma Résidence')</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.pwa')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="marketing-nav">
        <div class="container py-2 d-flex align-items-center gap-2">
            <x-logo href="{{ route('home') }}" />
            @auth
                <form method="POST" action="{{ route('logout') }}" class="logout-inline-form d-xl-none ms-auto">
                    @csrf
                    <button class="logout-inline" type="submit">Se déconnecter</button>
                </form>
            @endauth
            <button class="icon-btn d-xl-none {{ auth()->check() ? '' : 'ms-auto' }}" type="button" data-bs-toggle="offcanvas" data-bs-target="#publicNav" aria-label="Ouvrir le menu">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <nav class="public-nav d-none d-xl-flex align-items-center gap-3 ms-auto" aria-label="Public">
                <a class="fw-semibold text-decoration-none text-ink" href="{{ route('home') }}#plateforme">La plateforme</a>
                <a class="fw-semibold text-decoration-none text-ink" href="{{ route('home') }}#fonctionnalites">Fonctionnalités</a>
                <a class="fw-semibold text-decoration-none text-ink" href="{{ route('catalog.index') }}">Logements</a>
                @auth
                    <a class="btn btn-primary" href="{{ route('account.home') }}">Mon espace</a>
                    <form method="POST" action="{{ route('logout') }}" class="logout-inline-form">
                        @csrf
                        <button class="logout-inline" type="submit">Se déconnecter</button>
                    </form>
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
            @auth
                <a class="btn btn-primary" href="{{ route('account.home') }}">Mon espace</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-ghost" type="submit">Se déconnecter</button>
                </form>
            @else
                <a class="btn btn-ghost" href="{{ route('login') }}">Connexion</a>
                <a class="btn btn-primary" href="{{ route('register.landlord') }}">Commencer maintenant</a>
                <a class="btn btn-ghost" href="{{ route('register.tenant') }}">Je suis locataire</a>
            @endauth
        </div>
    </div>
    <main class="public-main">
        @yield('content')
    </main>
    <footer class="site-footer">
        <div class="container">
            <div class="footer-frame">
                <div class="footer-brand">
                    <img class="edos-logo" src="{{ asset('images/edos-services-logo.png') }}" alt="EDOS SERVICES" width="2000" height="667">
                    <div>
                        <p class="footer-name">EDOS SERVICES</p>
                        <p class="mb-0">Solutions numériques et logiciels de gestion conçus pour les entreprises.</p>
                    </div>
                </div>
                <div>
                    <p class="kicker">Ma Résidence</p>
                    <nav class="footer-links" aria-label="Pages de l'accueil">
                        <a href="{{ route('home') }}#plateforme">La plateforme</a>
                        <a href="{{ route('home') }}#fonctionnalites">Fonctionnalités</a>
                        <a href="{{ route('home') }}#biens">Biens et unités</a>
                        <a href="{{ route('home') }}#loyers">Loyers</a>
                        <a href="{{ route('home') }}#charges">Charges</a>
                        <a href="{{ route('home') }}#maintenance">Maintenance</a>
                    </nav>
                </div>
                <div>
                    <p class="kicker">Accès</p>
                    <nav class="footer-links" aria-label="Accès">
                        <a href="{{ route('home') }}">Accueil</a>
                        <a href="{{ route('catalog.index', ['parcourir' => 1]) }}">Logements</a>
                        <a href="{{ route('login') }}">Connexion</a>
                        <a href="{{ route('register.landlord') }}">Inscription bailleur</a>
                        <a href="{{ route('register.tenant') }}">Inscription locataire</a>
                        <a class="footer-phone" href="tel:+243992749668"><i class="bi bi-telephone" aria-hidden="true"></i><span>+243 992 749 668</span></a>
                    </nav>
                    <button type="button" class="footer-install d-none" data-pwa-install>Installer l'application</button>
                    <p class="footer-ios d-none mb-0" data-pwa-ios>Sur iPhone : Partager, puis « Sur l'écran d'accueil ».</p>
                </div>
            </div>
            <div class="footer-bar">
                <p class="mb-0">© {{ now()->year }} EDOS SERVICES</p>
                <p class="mb-0">Photographies : Unsplash</p>
            </div>
        </div>
    </footer>
</body>
</html>
