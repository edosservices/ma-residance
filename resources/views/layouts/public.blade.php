<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f6b43">
    <meta name="description" content="Ma Résidence aide les bailleurs à gérer logements, locataires, loyers, paiements, dépenses et maintenance.">
    <title>{{ $title ?? 'Ma Résidence' }}</title>
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
                <a class="fw-semibold text-decoration-none text-ink" href="{{ route('home') }}#solution">La solution</a>
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
            <a class="btn btn-ghost" href="{{ route('home') }}#solution">Découvrir la solution</a>
            <a class="btn btn-ghost" href="{{ route('catalog.index') }}">Logements libres</a>
            <a class="btn btn-ghost" href="{{ route('login') }}">Connexion</a>
            <a class="btn btn-primary" href="{{ route('register.landlord') }}">Commencer maintenant</a>
            <a class="btn btn-ghost" href="{{ route('register.tenant') }}">Je suis locataire</a>
        </div>
    </div>
    <main class="public-main">
        @yield('content')
    </main>
    <footer class="border-top bg-white">
        <div class="container py-4 d-flex flex-wrap justify-content-between gap-3">
            <x-logo href="{{ route('home') }}" />
            <p class="mb-0 text-muted small">Chaque bailleur a son espace. Chaque locataire ne voit que son logement.</p>
        </div>
    </footer>
</body>
</html>
