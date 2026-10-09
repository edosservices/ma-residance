<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f6b43">
    <title>@yield('document-title', 'Ma Résidence')</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.pwa')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="auth-split">
        <aside class="auth-aside d-none d-lg-flex">
            <img src="{{ asset('images/brand/residence-interieur.jpg') }}" alt="Intérieur clair d'une résidence contemporaine" width="1600" height="1200">
            <div class="auth-aside-scrim"></div>
            <div class="auth-aside-copy">
                <x-logo href="{{ route('home') }}" tone="light" tagline="Gestion locative" />
                <h2>@yield('aside-title', 'La gestion immobilière, avec une nouvelle exigence.')</h2>
                <p>@yield('aside-text', 'Propriétés, locataires, contrats, loyers et dépenses restent dans l’espace de chaque bailleur.')</p>
                <div class="auth-publisher">
                    <img class="edos-logo" src="{{ asset('images/edos-services-logo.png') }}" alt="EDOS SERVICES" width="2000" height="667">
                    <p>Édité par EDOS SERVICES</p>
                </div>
            </div>
        </aside>
        <main class="auth-main">
            <div class="auth-panel">
                <x-logo href="{{ route('home') }}" class="d-lg-none mb-4" />
                <x-flash />
                @yield('content')
                <img class="edos-logo d-lg-none" src="{{ asset('images/edos-services-logo.png') }}" alt="EDOS SERVICES" width="2000" height="667">
            </div>
        </main>
    </div>
</body>
</html>
