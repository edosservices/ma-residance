@php $role = $shellRole ?? null; @endphp
@if ($role === 'office')
    <nav class="quickbar d-xl-none" aria-label="Actions rapides">
        <a class="{{ request()->routeIs('office.dashboard') ? 'is-active' : '' }}" href="{{ route('office.dashboard') }}"><i class="bi bi-speedometer2" aria-hidden="true"></i>Accueil</a>
        @if (allows('invoices.view') || allows('payments.view') || allows('invoices.manage'))
            <a class="{{ request()->routeIs('office.invoices.*') ? 'is-active' : '' }}" href="{{ route('office.invoices.index') }}"><i class="bi bi-receipt" aria-hidden="true"></i>Factures</a>
        @elseif (allows('maintenance.manage'))
            <a class="{{ request()->routeIs('office.maintenance.*') ? 'is-active' : '' }}" href="{{ route('office.maintenance.index') }}"><i class="bi bi-tools" aria-hidden="true"></i>Maintenance</a>
        @elseif (allows('tenants.view'))
            <a href="{{ route('office.tenants.index') }}"><i class="bi bi-people" aria-hidden="true"></i>Locataires</a>
        @else
            <a href="{{ route('office.notifications.index') }}"><i class="bi bi-bell" aria-hidden="true"></i>Alertes</a>
        @endif
        @if (allows('collections.record') || allows('payments.validate'))
            <a href="{{ route('office.payments.create') }}"><i class="bi bi-plus-circle" aria-hidden="true"></i>Encaisser</a>
        @elseif (allows('maintenance.manage'))
            <a href="{{ route('office.maintenance.index') }}"><i class="bi bi-tools" aria-hidden="true"></i>Maintenance</a>
        @else
            <a href="{{ route('office.notifications.index') }}"><i class="bi bi-bell" aria-hidden="true"></i>Alertes</a>
        @endif
        <button type="button" data-bs-toggle="offcanvas" data-bs-target="#appNav" aria-controls="appNav"><i class="bi bi-list" aria-hidden="true"></i>Menu</button>
    </nav>
@elseif ($role === 'portal')
    <nav class="quickbar d-xl-none" aria-label="Actions rapides">
        <a class="{{ request()->routeIs('portal.dashboard') ? 'is-active' : '' }}" href="{{ route('portal.dashboard') }}"><i class="bi bi-house" aria-hidden="true"></i>Accueil</a>
        <a class="{{ request()->routeIs('portal.invoices.*') ? 'is-active' : '' }}" href="{{ route('portal.invoices.index') }}"><i class="bi bi-receipt" aria-hidden="true"></i>Factures</a>
        <a class="{{ request()->routeIs('portal.maintenance.*') ? 'is-active' : '' }}" href="{{ route('portal.maintenance.index') }}"><i class="bi bi-tools" aria-hidden="true"></i>Incident</a>
        <button type="button" data-bs-toggle="offcanvas" data-bs-target="#appNav" aria-controls="appNav"><i class="bi bi-list" aria-hidden="true"></i>Menu</button>
    </nav>
@elseif ($role === 'admin')
    <nav class="quickbar d-xl-none" aria-label="Actions rapides">
        <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2" aria-hidden="true"></i>Vue</a>
        <a class="{{ request()->routeIs('admin.organizations') ? 'is-active' : '' }}" href="{{ route('admin.organizations') }}"><i class="bi bi-buildings" aria-hidden="true"></i>Bailleurs</a>
        <a class="{{ request()->routeIs('admin.users') ? 'is-active' : '' }}" href="{{ route('admin.users') }}"><i class="bi bi-people" aria-hidden="true"></i>Comptes</a>
        <button type="button" data-bs-toggle="offcanvas" data-bs-target="#appNav" aria-controls="appNav"><i class="bi bi-list" aria-hidden="true"></i>Menu</button>
    </nav>
@endif
