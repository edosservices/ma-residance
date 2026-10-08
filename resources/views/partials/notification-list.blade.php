<div class="d-grid gap-2">
    @forelse ($items as $item)
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
        <a class="card d-block {{ $item->read_at ? 'opacity-70' : '' }}" href="{{ $item->data['url'] ?? ($fallback ?? '#') }}">
            <div class="d-flex gap-3">
                <span class="notif-icon" aria-hidden="true"><i class="bi bi-{{ $icon }}"></i></span>
                <span class="flex-1">
                    <span class="d-flex flex-wrap justify-content-between gap-2">
                        <strong>{{ $item->data['title'] ?? 'Notification' }}</strong>
                        <span class="mr-badge {{ $item->read_at ? 'mr-badge-free' : 'mr-badge-progress' }}">
                            <i class="bi {{ $item->read_at ? 'bi-check2' : 'bi-circle-fill' }}" aria-hidden="true"></i>
                            {{ $item->read_at ? 'Lu' : 'Non lu' }}
                        </span>
                    </span>
                    <span class="d-block small text-muted">{{ $type }} · {{ $when }}</span>
                    @if (! empty($item->data['body']))
                        <p class="mb-0 mt-1">{{ $item->data['body'] }}</p>
                    @endif
                </span>
            </div>
        </a>
    @empty
        <x-empty icon="bell" title="Aucune notification" text="Les paiements, retards, maintenances, messages et départs s'afficheront ici." />
    @endforelse
</div>
<div class="mt-3">{{ $items->links() }}</div>
