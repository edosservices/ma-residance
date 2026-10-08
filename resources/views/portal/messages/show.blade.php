@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">{{ $thread->participants->first(fn ($user) => $user->id !== auth()->id())?->name }}</h1>
    <div class="mt-4 space-y-2">
        @foreach ($thread->messages as $message)
            <div class="card">
                <p class="text-xs text-muted">{{ $message->sender?->name }} · {{ $message->created_at->timezone(config('app.timezone'))->format('d/m H:i') }}</p>
                <p class="mt-1 text-sm">{{ $message->body }}</p>
            </div>
        @endforeach
    </div>
    <form method="POST" action="{{ route('portal.messages.reply', $thread) }}" class="mt-4 space-y-2">
        @csrf
        <textarea class="field" name="body" required></textarea>
        <button class="btn btn-primary w-full">Répondre</button>
    </form>
@endsection
