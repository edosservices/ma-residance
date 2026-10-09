@extends('layouts.shell')
@section('content')
    <x-page-header title="Messages" subtitle="Échanges avec votre bailleur" />
    <div class="inbox">
        <aside class="inbox-list card">
            <label class="small fw-semibold" for="thread-filter">Rechercher</label>
            <input id="thread-filter" class="field mb-3" type="search" placeholder="Nom ou extrait" data-thread-filter>
            @forelse ($threads as $thread)
                @php $peer = $thread->participants->first(fn ($user) => $user->id !== auth()->id()); @endphp
                <a class="thread-row" href="{{ route('portal.messages.show', $thread) }}" data-thread-row>
                    <span class="account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($peer?->name ?? '?', 0, 1)) }}</span>
                    <span>
                        <strong>{{ $peer?->name ?? 'Conversation' }}</strong>
                        <small>{{ $thread->messages->first()?->body }}</small>
                    </span>
                </a>
            @empty
                <p class="text-muted mb-0">Aucune conversation.</p>
            @endforelse
        </aside>
        <section class="card inbox-compose">
            <h2 class="h5">Écrire au bailleur</h2>
            <form method="POST" action="{{ route('portal.messages.store') }}" enctype="multipart/form-data" class="d-grid gap-3">
                @csrf
                <label class="small fw-semibold" for="body">Message
                    <textarea id="body" class="field mt-1" name="body" placeholder="Écrire au bailleur" required></textarea>
                </label>
                <label class="small fw-semibold" for="attachment">Pièce jointe
                    <input id="attachment" class="field mt-1" type="file" name="attachment">
                </label>
                <button class="btn btn-primary">Envoyer</button>
            </form>
        </section>
    </div>
@endsection
