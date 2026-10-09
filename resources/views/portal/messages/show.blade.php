@extends('layouts.shell')
@section('content')
    @php $peer = $thread->participants->first(fn ($user) => $user->id !== auth()->id()); @endphp
    <x-page-header :title="$peer?->name ?? 'Conversation'" subtitle="Messages">
        <x-slot:actions>
            <a class="btn btn-ghost" href="{{ route('portal.messages.index') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Toutes les conversations</a>
        </x-slot:actions>
    </x-page-header>
    <div class="chat-log card">
        @foreach ($thread->messages as $message)
            <article class="bubble {{ $message->sender_id === auth()->id() ? 'bubble-mine' : 'bubble-theirs' }}">
                <p class="bubble-meta">{{ $message->sender?->name }} · {{ $message->created_at->timezone(config('app.timezone'))->format('d/m H:i') }}</p>
                <p class="mb-0">{{ $message->body }}</p>
                @if ($message->attachment_path)
                    <a class="fw-semibold" href="{{ file_url($message->attachment_path) }}">Pièce jointe</a>
                @endif
            </article>
        @endforeach
    </div>
    <form method="POST" action="{{ route('portal.messages.reply', $thread) }}" enctype="multipart/form-data" class="chat-composer card">
        @csrf
        <label class="visually-hidden" for="reply-body">Répondre</label>
        <textarea id="reply-body" class="field" name="body" placeholder="Répondre" required></textarea>
        <div class="file-field">
            <label class="btn btn-ghost mb-0" for="reply-file">Joindre un fichier</label>
            <span class="file-name" data-file-name>Aucun fichier</span>
            <input id="reply-file" class="visually-hidden" type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf">
        </div>
        <button class="btn btn-primary">Envoyer</button>
    </form>
@endsection