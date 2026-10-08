@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Bailleurs</h1>
    <div class="mt-4 space-y-2">
        @foreach ($organizations as $organization)
            <article class="card">
                <p class="font-semibold">{{ $organization->name }}</p>
                <p class="text-sm text-muted">{{ $organization->members_count }} membres · {{ $organization->status->label() }}</p>
                <form method="POST" action="{{ route('admin.organizations.status', $organization) }}" class="mt-2">
                    @csrf
                    <button class="btn btn-ghost">{{ $organization->status->value === 'active' ? 'Suspendre' : 'Réactiver' }}</button>
                </form>
            </article>
        @endforeach
    </div>
@endsection
