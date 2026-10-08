@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">{{ $item->title }}</h1>
    <x-badge class="mt-2" :tone="$item->status->tone()">{{ $item->status->label() }}</x-badge>
    <p class="card mt-3 text-sm">{{ $item->description }}</p>
    <div class="mt-3 space-y-2">
        @foreach ($item->updates as $update)
            <p class="text-sm text-muted">{{ $update->created_at->timezone(config('app.timezone'))->format('d/m H:i') }} · {{ $update->note }}</p>
        @endforeach
    </div>
    <form method="POST" action="{{ route('portal.maintenance.comment', $item) }}" enctype="multipart/form-data" class="mt-4 space-y-2">
        @csrf
        <textarea class="field" name="note" placeholder="Ajouter un commentaire" required></textarea>
        <input class="field" type="file" name="photo" accept="image/*">
        <button class="btn btn-ghost w-full">Envoyer</button>
    </form>
@endsection
