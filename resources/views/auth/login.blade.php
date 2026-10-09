@extends('layouts.guest')
@section('document-title', 'Connexion — Ma Résidence')
@section('aside-title', 'Un accès pour chaque rôle.')
@section('aside-text', 'Bailleur, collaborateur ou locataire : le même écran, des espaces séparés.')
@section('content')
    <h1>Connexion</h1>
    <p class="text-muted">Téléphone ou email.</p>
    <form method="POST" action="{{ route('login') }}" class="d-grid gap-3 mt-4">
        @csrf
        <div class="field-group">
            <label for="login">Téléphone ou email</label>
            <div class="field-icon">
                <i class="bi bi-person" aria-hidden="true"></i>
                <input id="login" class="field" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus>
            </div>
        </div>
        <x-password-field />
        <label class="d-flex align-items-center gap-2 small text-muted"><input type="checkbox" name="remember" value="1"> Rester connecté</label>
        <button class="btn btn-primary">Entrer</button>
    </form>
    <p class="small text-muted mt-3 mb-0">Pas encore de compte ? <a href="{{ route('register.landlord') }}">Espace bailleur</a> · <a href="{{ route('register.tenant') }}">Espace locataire</a></p>
@endsection
