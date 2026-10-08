<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Ma Résidence' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper font-sans text-ink antialiased">
    <div class="mx-auto flex min-h-screen w-full max-w-lg flex-col px-4 py-6">
        <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight">Ma Résidence</a>
        <main class="flex-1 py-6">
            <x-flash />
            @yield('content')
        </main>
    </div>
</body>
</html>
