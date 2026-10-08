@extends('layouts.shell')
@section('content')
    <x-page-header title="Activité" subtitle="Journal global de la plateforme" />
    @forelse ($logs as $log)
        <article class="card mb-2">
            <p class="small text-muted mb-1">{{ $log->user?->name ?? 'Système' }} · {{ $log->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
            <p class="mb-0">{{ $log->description }}</p>
        </article>
    @empty
        <x-empty title="Journal vide" />
    @endforelse
    <div class="mt-3">{{ $logs->links() }}</div>
@endsection
