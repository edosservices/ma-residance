@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Un problème ?</h1>
    <p class="text-muted">La déclaration est enregistrée chez le bailleur, avec votre nom, la date et le logement.</p>
    <form method="POST" action="{{ route('portal.maintenance.store') }}" enctype="multipart/form-data" class="mt-4">
        @csrf
        <div class="field-group mb-2">
            <label for="portal-title">Titre</label>
            <input id="portal-title" class="field" name="title" placeholder="Ex. La douche ne fonctionne plus" required>
        </div>
        <div class="field-group mb-2">
            <label for="portal-description">Description</label>
            <textarea id="portal-description" class="field" name="description" placeholder="Ex. Plus d'eau chaude depuis hier soir." required></textarea>
        </div>
        <div class="field-group mb-2">
            <label for="portal-urgency">Urgence</label>
            <select id="portal-urgency" class="field" name="urgency">
                <option value="low">Peut attendre</option>
                <option value="normal" selected>Normale</option>
                <option value="high">Urgente</option>
            </select>
            <span class="field-hint">Urgente : le bailleur et le gérant reçoivent une alerte jusqu'à ce que ce soit réglé.</span>
        </div>
        <div class="field-group mb-3">
            <label for="portal-photo">Photo</label>
            <input id="portal-photo" class="field" type="file" name="photo" accept="image/*">
            <span class="field-hint">Ex. photo du problème.</span>
        </div>
        <button class="btn btn-primary w-100">Signaler</button>
    </form>
    <div class="mt-4 space-y-2">
        @foreach ($items as $item)
            <a class="card block" href="{{ route('portal.maintenance.show', $item) }}">
                <p class="font-semibold">{{ $item->title }}</p>
                <x-badge :tone="$item->status->tone()">{{ $item->status->label() }}</x-badge>
                <p class="text-muted small mb-0">{{ $item->urgency->label() }} · {{ $item->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
            </a>
        @endforeach
    </div>
@endsection
