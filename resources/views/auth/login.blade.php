@extends('layouts.guest')
@section('content')
    <h1 class="h3">Connexion</h1>
    <p class="text-muted">Téléphone ou email, pour un bailleur, un collaborateur ou un locataire.</p>
    <form method="POST" action="{{ route('login') }}" class="d-grid gap-3 mt-4">
        @csrf
        <label class="small">Téléphone ou email<input class="field mt-1" name="login" value="{{ old('login') }}" required autofocus></label>
        <label class="small">Mot de passe<input class="field mt-1" type="password" name="password" required></label>
        <label class="d-flex align-items-center gap-2 small text-muted"><input type="checkbox" name="remember" value="1"> Rester connecté</label>
        <button class="btn btn-primary">Entrer</button>
    </form>
    <p class="small text-muted mt-3 mb-0">Pas encore de compte ? <a href="{{ route('register.landlord') }}">Espace bailleur</a> · <a href="{{ route('register.tenant') }}">Espace locataire</a></p>
@endsection
