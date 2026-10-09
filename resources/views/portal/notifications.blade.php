@extends('layouts.shell')
@section('content')
    <x-page-header title="Notifications" subtitle="Paiements, factures, maintenance et messages">
        <x-slot:actions>
            <form method="POST" action="{{ route('portal.notifications.read') }}">
                @csrf
                <button class="btn btn-ghost">Tout marquer comme lu</button>
            </form>
        </x-slot:actions>
    </x-page-header>
    @include('partials.notification-list', ['fallback' => route('portal.dashboard')])
@endsection
