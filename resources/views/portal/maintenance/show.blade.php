@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">{{ $item->title }}</h1>
    <x-badge class="mt-2" :tone="$item->status->tone()">{{ $item->status->label() }}</x-badge>
    <p class="text-muted mt-2">{{ $item->unit?->name }} · {{ $item->urgency->label() }}</p>
    <p class="card mt-3 text-sm">{{ $item->description }}</p>
    @if (sees_trace())
        @include('partials.maintenance-trace', ['item' => $item])
    @else
        <p class="text-muted mt-3">Le détail des étapes reste chez le bailleur. Vous voyez ici l'état de votre déclaration.</p>
    @endif
    <form method="POST" action="{{ route('portal.maintenance.comment', $item) }}" enctype="multipart/form-data" class="mt-4">
        @csrf
        <div class="field-group mb-2">
            <label for="portal-note">Complément</label>
            <textarea id="portal-note" class="field" name="note" placeholder="Ex. La fuite a empiré ce soir." required></textarea>
        </div>
        <div class="field-group mb-3">
            <label for="portal-follow-photo">Photo</label>
            <input id="portal-follow-photo" class="field" type="file" name="photo" accept="image/*">
        </div>
        <button class="btn btn-ghost w-100">Envoyer</button>
    </form>
@endsection
