<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0e6b52">
    <title>{{ $title ?? 'Ma Résidence' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper font-sans text-ink antialiased">
    <header class="sticky top-0 z-30 border-b border-line bg-paper/95 backdrop-blur">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-3">
            <div>
                <a href="{{ $home ?? route('home') }}" class="text-base font-semibold tracking-tight">{{ $currentOrganization->name ?? 'Ma Résidence' }}</a>
                <p class="text-xs text-muted">{{ $eyebrow ?? '' }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($currentUser)
                    <a href="{{ $alerts ?? route('office.notifications.index') }}" class="relative rounded-full border border-line bg-white px-3 py-2 text-sm font-semibold">
                        Alertes
                        @if ($unreadNotifications > 0)
                            <span class="ml-1 rounded-full bg-brand px-1.5 text-xs text-white">{{ $unreadNotifications }}</span>
                        @endif
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-full px-2 py-2 text-sm text-muted">Sortir</button>
                    </form>
                @endif
            </div>
        </div>
    </header>
    <main class="mx-auto w-full max-w-3xl px-4 pb-28 pt-4">
        <x-flash />
        @yield('content')
    </main>
    @isset($shellNav)
        @include($shellNav)
    @endisset
</body>
</html>
