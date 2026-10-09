@extends('layouts.shell')
@section('content')
    <x-page-header title="Messages" subtitle="Échanges avec les locataires et l'équipe" />
    <div class="inbox">
        <aside class="inbox-list card">
            <label class="small fw-semibold" for="thread-filter">Rechercher</label>
            <input id="thread-filter" class="field mb-3" type="search" placeholder="Nom ou extrait" data-thread-filter>
            @forelse ($threads as $thread)
                @php $peer = $thread->participants->first(fn ($user) => $user->id !== auth()->id()); @endphp
                <a class="thread-row" href="{{ route('office.messages.show', $thread) }}" data-thread-row>
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
            <h2 class="h5">Nouveau message</h2>
            <form method="POST" action="{{ route('office.messages.store') }}" enctype="multipart/form-data" class="d-grid gap-3">
                @csrf
                <label class="small fw-semibold" for="recipient_id">Destinataire
                    <select id="recipient_id" class="field mt-1" name="recipient_id" required>
                        <optgroup label="Locataires">
                            @foreach ($tenants as $tenant)
                                @if ($tenant->user_id)
                                    <option value="{{ $tenant->user_id }}">{{ $tenant->name }}</option>
                                @endif
                            @endforeach
                        </optgroup>
                        <optgroup label="Équipe">
                            @foreach ($members as $member)
                                <option value="{{ $member->user_id }}">{{ $member->user->name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </label>
                <label class="small fw-semibold" for="body">Message
                    <textarea id="body" class="field mt-1" name="body" placeholder="Écrire un message" required></textarea>
                </label>
                <div class="file-field">
                    <label class="btn btn-ghost mb-0" for="attachment">Joindre un fichier</label>
                    <span class="file-name" data-file-name>Aucun fichier</span>
                    <input id="attachment" class="visually-hidden" type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf">
                </div>
                <button class="btn btn-primary">Envoyer</button>
            </form>
        </section>
    </div>
@endsection
