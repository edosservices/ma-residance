@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Journal global</h1>
    <div class="mt-4 space-y-2">
        @foreach ($logs as $log)
            <article class="card">
                <p class="text-xs text-muted">{{ $log->user?->name ?? 'Système' }} · {{ $log->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                <p class="text-sm">{{ $log->description }}</p>
            </article>
        @endforeach
    </div>
@endsection
