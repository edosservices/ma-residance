@php $count = $unreadNotifications ?? 0; @endphp
<div class="dropdown">
    <button class="icon-btn bell-btn" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Notifications{{ $count > 0 ? ', '.$count.' non lues' : '' }}">
        <i class="bi bi-bell" aria-hidden="true"></i>
        @if ($count > 0)
            <span class="bell-count">{{ $count > 99 ? '99+' : $count }}</span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-end notif-menu">
        <div class="d-flex align-items-center justify-content-between px-2 py-2">
            <strong>Notifications</strong>
            @if ($count > 0)
                <span class="mr-badge mr-badge-paid">{{ $count }} non lue{{ $count > 1 ? 's' : '' }}</span>
            @endif
        </div>
        @forelse ($recentNotifications ?? [] as $item)
            @php
                $kind = (string) ($item->data['kind'] ?? '');
                [$icon, $type] = match (true) {
                    str_contains($kind, 'payment') || str_contains($kind, 'invoice') || str_contains($kind, 'rent') || str_contains($kind, 'collection') || str_contains($kind, 'remittance') => ['cash-coin', 'Paiement'],
                    str_contains($kind, 'maintenance') => ['tools', 'Maintenance'],
                    str_contains($kind, 'message') => ['chat-dots', 'Message'],
                    str_contains($kind, 'moveout') || str_contains($kind, 'depart') => ['box-arrow-right', 'Départ'],
                    str_contains($kind, 'rental') || str_contains($kind, 'contract') => ['file-earmark-text', 'Demande'],
                    str_contains($kind, 'broadcast') => ['megaphone', 'Annonce'],
                    default => ['bell', 'Notification'],
                };
                $when = $item->created_at?->timezone(config('app.timezone'))->translatedFormat('d M Y · H:i');
            @endphp
            <a class="notif-item {{ $item->read_at ? '' : 'is-unread' }}" href="{{ $item->data['url'] ?? ($alerts ?? '#') }}">
                <span class="notif-icon" aria-hidden="true"><i class="bi bi-{{ $icon }}"></i></span>
                <span class="min-w-0">
                    <span class="d-flex flex-wrap gap-2 align-items-center">
                        <strong>{{ $item->data['title'] ?? 'Notification' }}</strong>
                        <span class="mr-badge {{ $item->read_at ? 'mr-badge-free' : 'mr-badge-progress' }}">{{ $item->read_at ? 'Lu' : 'Non lu' }}</span>
                    </span>
                    <span class="d-block small text-muted">{{ $type }} · {{ $when }}</span>
                    @if (! empty($item->data['body']))
                        <span class="d-block small mt-1">{{ $item->data['body'] }}</span>
                    @endif
                </span>
            </a>
        @empty
            <p class="text-muted small px-2 py-3 mb-0">Aucune notification pour le moment.</p>
        @endforelse
        <div class="d-flex justify-content-between gap-2 px-2 py-2 border-top mt-1">
            <a class="small fw-semibold" href="{{ $alerts ?? route('home') }}">Tout voir</a>
            @if ($count > 0 && in_array(($shellRole ?? ''), ['office', 'portal'], true))
                <form method="POST" action="{{ $shellRole === 'portal' ? route('portal.notifications.read') : route('office.notifications.read') }}">
                    @csrf
                    <button class="btn btn-link btn-sm p-0 fw-semibold">Tout marquer comme lu</button>
                </form>
            @endif
        </div>
    </div>
</div>
