@extends('layouts.shell')
@section('content')
    <x-page-header title="Notifications" subtitle="Demandes, paiements, retards, maintenance, messages et départs">
        <x-slot:actions>
            <form method="POST" action="{{ route('office.notifications.read') }}">
                @csrf
                <button class="btn btn-ghost">Tout marquer comme lu</button>
            </form>
            @if (allows('notifications.send'))
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#sendNotice"><i class="bi bi-megaphone" aria-hidden="true"></i> Envoyer</button>
            @endif
        </x-slot:actions>
    </x-page-header>
    @include('partials.notification-list', ['fallback' => route('office.dashboard')])
    @if (allows('notifications.send'))
        <div class="modal fade" id="sendNotice" tabindex="-1" aria-labelledby="sendNoticeLabel" @if ($errors->any()) data-open-on-load @endif>
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <form method="POST" action="{{ route('office.notifications.store') }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="sendNoticeLabel">Annonce aux locataires</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body d-grid gap-2">
                        <select class="field" name="audience" aria-label="Destinataires">
                            <option value="all">Tous les locataires</option>
                            <option value="one">Un locataire</option>
                            <option value="several">Plusieurs locataires</option>
                            <option value="property">Une propriété</option>
                            <option value="unit">Un logement</option>
                        </select>
                        <select class="field" name="tenant_id" aria-label="Un locataire"><option value="">Un locataire</option>@foreach ($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->name }}</option>@endforeach</select>
                        <select class="field" name="tenant_ids[]" multiple size="4" aria-label="Plusieurs locataires">
                            @foreach ($tenants as $tenant)
                                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                            @endforeach
                        </select>
                        <select class="field" name="property_id" aria-label="Propriété"><option value="">Propriété</option>@foreach ($properties as $property)<option value="{{ $property->id }}">{{ $property->name }}</option>@endforeach</select>
                        <select class="field" name="unit_id" aria-label="Logement"><option value="">Logement</option>@foreach ($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select>
                        <input class="field" name="title" placeholder="Titre" required>
                        <textarea class="field" name="body" placeholder="Message" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Annuler</button>
                        <button class="btn btn-primary">Envoyer</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
