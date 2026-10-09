@extends('layouts.guest')
@section('document-title', 'Inscription bailleur — Ma Résidence')
@section('aside-title', 'Votre organisation reste isolée.')
@section('aside-text', 'Les autres bailleurs n’accèdent ni à vos logements, ni à vos encaissements.')
@section('content')
    <h1>Créer mon espace bailleur</h1>
    <p class="text-muted">Le compte ouvre une organisation distincte.</p>
    <form method="POST" action="{{ route('register.landlord') }}" class="d-grid gap-3 mt-4">
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
        <div class="field-group">
            <label for="organization_name">Nom de la résidence</label>
            <div class="field-icon">
                <i class="bi bi-building" aria-hidden="true"></i>
                <input id="organization_name" class="field" name="organization_name" value="{{ old('organization_name') }}" required>
            </div>
        </div>
        <x-password-field id="password" autocomplete="new-password" />
        <x-password-field name="password_confirmation" id="password_confirmation" label="Confirmer le mot de passe" autocomplete="new-password" />
        <button class="btn btn-primary">Créer mon organisation</button>
    </form>
    <p class="small text-muted mt-3 mb-0">Déjà un espace ? <a href="{{ route('login') }}">Connexion</a></p>
@endsection
