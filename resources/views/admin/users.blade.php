@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Comptes</h1>
    <div class="mt-4 space-y-2">
        @foreach ($users as $user)
            <article class="card">
                <p class="font-semibold">{{ $user->name }}</p>
                <p class="text-sm text-muted">{{ $user->phone }} · {{ $user->status->label() }}</p>
                @unless ($user->is_super_admin)
                    <form method="POST" action="{{ route('admin.users.status', $user) }}" class="mt-2">@csrf<button class="btn btn-ghost">{{ $user->status->value === 'active' ? 'Suspendre' : 'Réactiver' }}</button></form>
                @endunless
            </article>
        @endforeach
    </div>
@endsection
