@extends('layouts.guest')
@section('document-title', 'Inscription locataire — Ma Résidence')
@section('aside-title', 'Votre logement, rien d’autre.')
@section('aside-text', 'Le locataire suit son loyer, ses factures et ses demandes. Les autres dossiers restent fermés.')
@section('content')
    <h1>Compte locataire</h1>
    <p class="text-muted">Vous ne verrez que votre logement, vos factures et vos échanges.</p>
    <form method="POST" action="{{ route('register.tenant') }}" class="d-grid gap-3 mt-4">
        @csrf
        <div class="field-group">
            <label for="name">Votre nom</label>
            <div class="field-icon">
                <i class="bi bi-person" aria-hidden="true"></i>
                <input id="name" class="field" name="name" value="{{ old('name') }}" autocomplete="name" required>
            </div>
        </div>
        <div class="field-group">
            <label for="phone">Téléphone</label>
            <div class="field-icon">
                <i class="bi bi-telephone" aria-hidden="true"></i>
                <input id="phone" class="field" name="phone" value="{{ old('phone') }}" autocomplete="tel" required>
            </div>
        </div>
        <div class="field-group">
            <label for="email">Email facultatif</label>
            <div class="field-icon">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <input id="email" class="field" type="email" name="email" value="{{ old('email') }}" autocomplete="email">
            </div>
        </div>
        <x-password-field id="password" autocomplete="new-password" />
        <x-password-field name="password_confirmation" id="password_confirmation" label="Confirmer le mot de passe" autocomplete="new-password" />
        <button class="btn btn-primary">Créer mon compte</button>
    </form>
    <p class="small text-muted mt-3 mb-0">Vous êtes bailleur ? <a href="{{ route('register.landlord') }}">Créer une organisation</a> · <a href="{{ route('login') }}">Connexion</a></p>
@endsection