@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Équipe</h1>
    <details class="card mt-4">
        <summary class="cursor-pointer font-semibold">Ajouter un collaborateur</summary>
        <form method="POST" action="{{ route('office.members.store') }}" class="mt-3 space-y-2">
            @csrf
            <input class="field" name="name" placeholder="Nom" required>
            <input class="field" name="phone" placeholder="Téléphone" required>
            <input class="field" name="email" placeholder="Email facultatif">
            <input class="field" type="password" name="password" placeholder="Mot de passe initial" required>
            <select class="field" name="role" id="role">
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                @endforeach
            </select>
            <p class="text-xs text-muted">Si le téléphone existe déjà, le mot de passe actuel est conservé.</p>
            <div class="space-y-1">
                @foreach ($permissions as $permission)
                    <label class="flex items-center gap-2 text-sm" data-cap="{{ $permission->value }}">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->value }}">
                        {{ $permission->label() }}
                    </label>
                @endforeach
            </div>
            <button class="btn btn-primary w-full">Créer l'accès</button>
        </form>
    </details>
    <div class="mt-4 space-y-3">
        @foreach ($members as $member)
            <article class="card">
                <p class="font-semibold">{{ $member->user->name }}</p>
                <p class="text-sm text-muted">{{ $member->role->label() }} · {{ $member->user->phone }}</p>
                @if ($member->role->value !== 'owner')
                    <form method="POST" action="{{ route('office.members.update', $member) }}" class="mt-3 space-y-1">
                        @csrf
                        @method('PUT')
                        @foreach ($permissions as $permission)
                            @if (in_array($permission->value, $matrix[$member->role->value], true))
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->value }}" @checked(in_array($permission->value, $member->permissions ?? $matrix[$member->role->value], true))>
                                    {{ $permission->label() }}
                                </label>
                            @endif
                        @endforeach
                        <div class="flex gap-2 pt-2">
                            <button class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('office.members.update', $member) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="active" value="{{ $member->status === 'active' ? 0 : 1 }}">
                        <button class="mt-2 text-sm text-muted">{{ $member->status === 'active' ? 'Suspendre' : 'Réactiver' }}</button>
                    </form>
                @endif
            </article>
        @endforeach
    </div>
    <script>
        const caps = @json($matrix);
        const role = document.getElementById('role');
        const sync = () => {
            const allowed = caps[role.value] || [];
            document.querySelectorAll('[data-cap]').forEach((label) => {
                const input = label.querySelector('input');
                const on = allowed.includes(input.value);
                label.hidden = ! on;
                input.disabled = ! on;
                input.checked = on;
            });
        };
        role?.addEventListener('change', sync);
        sync();
    </script>
@endsection
