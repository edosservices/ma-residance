@extends('layouts.guest')
@section('content')
    <h1 class="text-3xl font-semibold">Connexion</h1>
    <p class="mt-2 text-sm text-muted">Téléphone ou email.</p>
    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-3">
        @csrf
        <input class="field" name="login" placeholder="Téléphone" value="{{ old('login') }}" required autofocus>
        <input class="field" type="password" name="password" placeholder="Mot de passe" required>
        <label class="flex items-center gap-2 text-sm text-muted"><input type="checkbox" name="remember" value="1"> Rester connecté</label>
        <button class="btn btn-primary w-full">Entrer</button>
    </form>
@endsection
