@extends('layouts.shell')
@section('content')
    <x-page-header title="Paramètres" subtitle="Identité de la plateforme" />
    <form method="POST" action="{{ route('admin.settings.update') }}" class="card d-grid gap-2">
        @csrf
        @method('PUT')
        <label class="small">Nom de la plateforme<input class="field mt-1" name="platform_name" value="{{ $settings['platform_name']['text'] ?? 'Ma Résidence' }}" required></label>
        <label class="small">Téléphone support<input class="field mt-1" name="support_phone" value="{{ $settings['support_phone']['text'] ?? '' }}"></label>
        <button class="btn btn-primary">Enregistrer</button>
    </form>
@endsection
