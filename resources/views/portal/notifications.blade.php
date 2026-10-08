@extends('layouts.shell')
@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Notifications</h1>
        <form method="POST" action="{{ route('portal.notifications.read') }}">@csrf<button class="text-sm font-semibold text-brand">Tout lire</button></form>
    </div>
    <div class="mt-4 space-y-2">
        @foreach ($items as $item)
            <a class="card block" href="{{ $item->data['url'] ?? route('portal.dashboard') }}">
                <p class="font-semibold">{{ $item->data['title'] ?? '' }}</p>
                <p class="text-sm text-muted">{{ $item->data['body'] ?? '' }}</p>
            </a>
        @endforeach
    </div>
@endsection
