@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Plateforme</h1>
    <div class="mt-4 grid grid-cols-2 gap-3">
        <x-stat label="Organisations" :value="$organizations" />
        <x-stat label="Actives" :value="$activeOrganizations" />
        <x-stat label="Utilisateurs" :value="$users" />
        <x-stat label="Suspendus" :value="$suspendedUsers" />
        <x-stat label="Paiements validés" :value="$approvedPayments" />
    </div>
@endsection
