<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f6b43">
    <title>{{ $title ?? 'Ma Résidence' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="public-main container py-4 py-lg-5">
        <div class="auth-wrap">
            <x-logo href="{{ route('home') }}" class="mb-4" />
            <section class="card">
                <x-flash />
                @yield('content')
            </section>
        </div>
    </main>
</body>
</html>
