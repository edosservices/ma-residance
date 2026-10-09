@extends('layouts.shell')
@section('content')
    @php $peer = $thread->participants->first(fn ($user) => $user->id !== auth()->id()); @endphp
    <x-page-header title="Messages" subtitle="{{ $peer?->name ?? 'Conversation' }}" />
    <div class="inbox inbox-chat">
        <aside class="inbox-list card">
            <label class="small fw-semibold" for="thread-filter">Rechercher</label>
            <input id="thread-filter" class="field mb-3" type="search" placeholder="Nom ou extrait" data-thread-filter>
            @forelse ($threads as $item)
                @php $itemPeer = $item->participants->first(fn ($user) => $user->id !== auth()->id()); @endphp
                <a class="thread-row {{ $item->id === $thread->id ? 'is-active' : '' }}" href="{{ route('office.messages.show', $item) }}" data-thread-row>
                    <span class="account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($itemPeer?->name ?? '?', 0, 1)) }}</span>
                    <span>
                        <strong>{{ $itemPeer?->name ?? 'Conversation' }}</strong>
                        <small>{{ $item->messages->first()?->body }}</small>
                    </span>
                </a>
            @empty
                <p class="text-muted mb-0">Aucune conversation.</p>
            @endforelse
        </aside>
        <section>
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
            <form method="POST" action="{{ route('office.messages.reply', $thread) }}" enctype="multipart/form-data" class="chat-composer card">
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
        </section>
    </div>
@endsection
