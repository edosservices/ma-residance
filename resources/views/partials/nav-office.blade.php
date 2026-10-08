@php
    $links = [
        [route('office.dashboard'), 'Accueil', request()->routeIs('office.dashboard')],
    ];
    if (allows('units.manage') || allows('properties.manage')) {
        $links[] = [route('office.properties.index'), 'Biens', request()->routeIs('office.properties.*', 'office.units.*')];
    } elseif (allows('tenants.view')) {
        $links[] = [route('office.tenants.index'), 'Locataires', request()->routeIs('office.tenants.*')];
    }
    if (allows('invoices.view') || allows('payments.view')) {
        $links[] = [route('office.invoices.index'), 'Factures', request()->routeIs('office.invoices.*', 'office.payments.*')];
    } elseif (allows('maintenance.manage')) {
        $links[] = [route('office.maintenance.index'), 'Interventions', request()->routeIs('office.maintenance.*')];
    }
    if (allows('collections.record') || allows('collections.remit')) {
        $links[] = [route('office.collections.index'), 'Caisse', request()->routeIs('office.collections.*', 'office.remittances.*')];
    }
    $links[] = [route('office.more'), 'Plus', request()->routeIs('office.more')];
@endphp
<nav class="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-card/95 backdrop-blur">
    <div class="mx-auto flex max-w-3xl justify-around px-1 py-2">
        @foreach ($links as [$url, $label, $active])
            <a href="{{ $url }}" class="rounded-xl px-2 py-2 text-xs font-semibold {{ $active ? 'text-brand' : 'text-muted' }}">{{ $label }}</a>
        @endforeach
    </div>
</nav>
