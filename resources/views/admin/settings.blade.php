@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Paramètres globaux</h1>
    <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-4 space-y-2">
        @csrf
        @method('PUT')
        <input class="field" name="platform_name" value="{{ $settings['platform_name']['text'] ?? 'Ma Résidence' }}" placeholder="Nom de la plateforme">
        <input class="field" name="support_phone" value="{{ $settings['support_phone']['text'] ?? '' }}" placeholder="Téléphone support">
        <button class="btn btn-primary w-full">Enregistrer</button>
    </form>
@endsection
