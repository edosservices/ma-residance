@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Messages</h1>
    <form method="POST" action="{{ route('office.messages.store') }}" enctype="multipart/form-data" class="card mt-4 space-y-2">
        @csrf
        <select class="field" name="recipient_id" required>
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
        <textarea class="field" name="body" placeholder="Message" required></textarea>
        <input class="field" type="file" name="attachment">
        <button class="btn btn-primary w-full">Envoyer</button>
    </form>
    <div class="mt-4 space-y-2">
        @foreach ($threads as $thread)
            <a class="card block" href="{{ route('office.messages.show', $thread) }}">
                <p class="font-semibold">{{ $thread->participants->first(fn ($user) => $user->id !== auth()->id())?->name }}</p>
                <p class="text-sm text-muted">{{ $thread->messages->first()?->body }}</p>
            </a>
        @endforeach
    </div>
@endsection
