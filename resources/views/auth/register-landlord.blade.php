@extends('layouts.guest')
@section('content')
    <h1 class="h3">Créer mon espace bailleur</h1>
    <p class="text-muted">Votre organisation reste isolée des autres bailleurs.</p>
    <form method="POST" action="{{ route('register.landlord') }}" class="d-grid gap-3 mt-4">
        @csrf
        <label class="small">Votre nom<input class="field mt-1" name="name" value="{{ old('name') }}" required></label>
        <label class="small">Téléphone<input class="field mt-1" name="phone" value="{{ old('phone') }}" required></label>
        <label class="small">Email facultatif<input class="field mt-1" type="email" name="email" value="{{ old('email') }}"></label>
        <label class="small">Nom de la résidence<input class="field mt-1" name="organization_name" value="{{ old('organization_name') }}" required></label>
        <label class="small">Mot de passe<input class="field mt-1" type="password" name="password" required></label>
        <label class="small">Confirmer<input class="field mt-1" type="password" name="password_confirmation" required></label>
        <button class="btn btn-primary">Créer mon organisation</button>
    </form>
@endsection
