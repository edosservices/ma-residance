@extends('layouts.guest')
@section('content')
    <h1 class="h3">Compte locataire</h1>
    <p class="text-muted">Vous ne verrez que votre logement, vos factures et vos échanges.</p>
    <form method="POST" action="{{ route('register.tenant') }}" class="d-grid gap-3 mt-4">
        @csrf
        <label class="small">Votre nom<input class="field mt-1" name="name" value="{{ old('name') }}" required></label>
        <label class="small">Téléphone<input class="field mt-1" name="phone" value="{{ old('phone') }}" required></label>
        <label class="small">Email facultatif<input class="field mt-1" type="email" name="email" value="{{ old('email') }}"></label>
        <label class="small">Mot de passe<input class="field mt-1" type="password" name="password" required></label>
        <label class="small">Confirmer<input class="field mt-1" type="password" name="password_confirmation" required></label>
        <button class="btn btn-primary">Créer mon compte</button>
    </form>
@endsection
