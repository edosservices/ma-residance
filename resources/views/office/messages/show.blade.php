@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">{{ $thread->participants->first(fn ($user) => $user->id !== auth()->id())?->name }}</h1>
    <div class="mt-4 space-y-2">
        @foreach ($thread->messages as $message)
            <div class="card {{ $message->sender_id === auth()->id() ? 'bg-sand' : '' }}">
                <p class="text-xs text-muted">{{ $message->sender?->name }} · {{ $message->created_at->timezone(config('app.timezone'))->format('d/m H:i') }}</p>
                <p class="mt-1 text-sm">{{ $message->body }}</p>
                @if ($message->attachment_path)
                    <a class="text-sm font-semibold text-brand" href="{{ file_url($message->attachment_path) }}">Pièce jointe</a>
                @endif
            </div>
        @endforeach
    </div>
    <form method="POST" action="{{ route('office.messages.reply', $thread) }}" enctype="multipart/form-data" class="mt-4 space-y-2">
        @csrf
        <textarea class="field" name="body" placeholder="Répondre" required></textarea>
        <input class="field" type="file" name="attachment">
        <button class="btn btn-primary w-full">Envoyer</button>
    </form>
@endsection
