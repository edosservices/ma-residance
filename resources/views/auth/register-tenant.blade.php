@extends('layouts.guest')
@section('content')
    <h1 class="text-3xl font-semibold">Compte locataire</h1>
    <form method="POST" action="{{ route('register.tenant') }}" class="mt-6 space-y-3">
        @csrf
        <input class="field" name="name" placeholder="Votre nom" value="{{ old('name') }}" required>
        <input class="field" name="phone" placeholder="Téléphone" value="{{ old('phone') }}" required>
        <input class="field" type="email" name="email" placeholder="Email (facultatif)" value="{{ old('email') }}">
        <input class="field" type="password" name="password" placeholder="Mot de passe" required>
        <input class="field" type="password" name="password_confirmation" placeholder="Confirmer" required>
        <button class="btn btn-primary w-full">Créer mon compte</button>
    </form>
@endsection
