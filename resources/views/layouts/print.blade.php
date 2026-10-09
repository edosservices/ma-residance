<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('document-title', 'Acte de reconnaissance')</title>
    <style>
        :root { color-scheme: light; }
        body { margin: 0; background: #f6f4f0; color: #1a211c; font-family: Georgia, 'Times New Roman', serif; }
        .sheet { width: min(820px, calc(100% - 1.5rem)); margin: 1rem auto 2rem; background: #fff; border: 1px solid #e3e0d8; }
        .banner { background: #0f6b43; color: #fff; padding: 1.1rem 1.4rem; }
        .banner p { margin: 0.2rem 0 0; letter-spacing: 0.08em; text-transform: uppercase; font-size: 0.78rem; }
        .banner h1 { margin: 0; font-size: 1.55rem; }
        .body { padding: 1.2rem 1.4rem 1.6rem; line-height: 1.55; }
        .body h2 { margin: 1.1rem 0 0.3rem; font-size: 1rem; }
        .seal { margin-top: 1.2rem; padding: 0.9rem 1rem; border: 2px solid #0f6b43; }
        .seal strong { color: #0f6b43; }
        .actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 0 0 1rem; }
        .actions a, .actions button { font-family: sans-serif; min-height: 40px; padding: 0.4rem 0.8rem; border-radius: 8px; border: 1px solid #0f6b43; background: #0f6b43; color: #fff; text-decoration: none; }
        .actions a.ghost, .actions button.ghost { background: #fff; color: #0f6b43; }
        img.sign { max-width: 180px; max-height: 72px; display: block; margin-top: 0.6rem; }
        @media print {
            body { background: #fff; }
            .actions { display: none; }
            .sheet { width: auto; margin: 0; border: 0; }
        }
        @media (max-width: 640px) {
            .banner, .body { padding-left: 1rem; padding-right: 1rem; }
        }
    </style>
</head>
<body>
    <main class="sheet">
        @yield('content')
    </main>
</body>
</html>
