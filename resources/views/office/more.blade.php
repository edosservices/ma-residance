@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Plus</h1>
    <div class="mt-4 grid gap-2">
        @php
            $items = [
                ['tenants.view|tenants.manage', route('office.tenants.index'), 'Locataires'],
                ['contracts.view|contracts.manage', route('office.requests.index'), 'Demandes'],
                ['contracts.view|contracts.manage', route('office.contracts.index'), 'Contrats'],
                ['payments.view|payments.validate', route('office.payments.index'), 'Paiements'],
                ['collections.remit|remittances.confirm', route('office.remittances.index'), 'Remises'],
                ['expenses.view|expenses.manage', route('office.expenses.index'), 'Dépenses'],
                ['maintenance.manage', route('office.maintenance.index'), 'Maintenance'],
                ['reports.view|reports.financial', route('office.reports'), 'Rapports'],
                ['messages.use', route('office.messages.index'), 'Messages'],
                ['notifications.send', route('office.notifications.index'), 'Notifications'],
                ['members.manage', route('office.members.index'), 'Équipe'],
                ['settings.manage', route('office.settings.edit'), 'Paramètres'],
                ['settings.manage|reports.financial', route('office.audit'), 'Journal'],
            ];
        @endphp
        @foreach ($items as [$permissions, $url, $label])
            @if (collect(explode('|', $permissions))->contains(fn ($permission) => allows($permission)))
                <a class="card font-semibold" href="{{ $url }}">{{ $label }}</a>
            @endif
        @endforeach
    </div>
@endsection
