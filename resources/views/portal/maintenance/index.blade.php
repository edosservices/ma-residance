@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Un problème ?</h1>
    <form method="POST" action="{{ route('portal.maintenance.store') }}" enctype="multipart/form-data" class="mt-4 space-y-2">
        @csrf
        <input class="field" name="title" placeholder="Ex. La douche ne fonctionne plus" required>
        <textarea class="field" name="description" placeholder="Décrivez" required></textarea>
        <select class="field" name="urgency"><option value="low">Peut attendre</option><option value="normal" selected>Normal</option><option value="high">Urgent</option></select>
        <input class="field" type="file" name="photo" accept="image/*">
        <button class="btn btn-primary w-full">Signaler</button>
    </form>
    <div class="mt-4 space-y-2">
        @foreach ($items as $item)
            <a class="card block" href="{{ route('portal.maintenance.show', $item) }}">
                <p class="font-semibold">{{ $item->title }}</p>
                <x-badge :tone="$item->status->tone()">{{ $item->status->label() }}</x-badge>
            </a>
        @endforeach
    </div>
@endsection
