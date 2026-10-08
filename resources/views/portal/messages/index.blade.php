@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Messages</h1>
    <form method="POST" action="{{ route('portal.messages.store') }}" enctype="multipart/form-data" class="mt-4 space-y-2">
        @csrf
        <textarea class="field" name="body" placeholder="Écrire au bailleur" required></textarea>
        <input class="field" type="file" name="attachment">
        <button class="btn btn-primary w-full">Envoyer</button>
    </form>
    <div class="mt-4 space-y-2">
        @foreach ($threads as $thread)
            <a class="card block" href="{{ route('portal.messages.show', $thread) }}">
                <p class="font-semibold">{{ $thread->participants->first(fn ($user) => $user->id !== auth()->id())?->name }}</p>
                <p class="text-sm text-muted">{{ $thread->messages->first()?->body }}</p>
            </a>
        @endforeach
    </div>
@endsection
