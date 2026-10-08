@php
    $links = [
        [route('admin.dashboard'), 'Vue', request()->routeIs('admin.dashboard')],
        [route('admin.organizations'), 'Bailleurs', request()->routeIs('admin.organizations')],
        [route('admin.users'), 'Comptes', request()->routeIs('admin.users')],
        [route('admin.audit'), 'Journal', request()->routeIs('admin.audit')],
        [route('admin.settings'), 'Réglages', request()->routeIs('admin.settings')],
    ];
@endphp
<nav class="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-card/95 backdrop-blur">
    <div class="mx-auto flex max-w-3xl justify-around px-1 py-2">
        @foreach ($links as [$url, $label, $active])
            <a href="{{ $url }}" class="rounded-xl px-2 py-2 text-xs font-semibold {{ $active ? 'text-brand' : 'text-muted' }}">{{ $label }}</a>
        @endforeach
    </div>
</nav>
