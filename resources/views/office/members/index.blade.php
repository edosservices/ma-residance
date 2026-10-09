@extends('layouts.shell')
@section('content')
    <x-page-header title="Équipe" subtitle="Le bailleur crée le compte. Le collaborateur se connecte avec ce téléphone et ce mot de passe." />
    @if (session('access_slip'))
        @php $slip = session('access_slip'); @endphp
        <article class="card access-slip mb-3">
            <p class="kicker">Identifiants à remettre en main propre</p>
            <p class="mb-2">Ces informations ne seront plus affichées. Notez-les avant de quitter la page.</p>
            <p class="mb-1"><strong>{{ $slip['name'] }}</strong> · {{ $slip['role'] }}</p>
            <p class="mb-1">Identifiant : <strong>{{ $slip['phone'] }}</strong></p>
            @if ($slip['password'])
                <p class="mb-0">Mot de passe : <strong>{{ $slip['password'] }}</strong></p>
            @else
                <p class="mb-0">Cette personne a déjà un compte. Elle se connecte avec son mot de passe actuel. Le mot de passe saisi ici n’a pas été enregistré.</p>
            @endif
        </article>
    @endif
    <details class="card" open>
        <summary class="fw-semibold">Ajouter un collaborateur</summary>
        <form method="POST" action="{{ route('office.members.store') }}" class="mt-3">
            @csrf
            <div class="field-group mb-2">
                <label for="member-name">Nom</label>
                <input id="member-name" class="field" name="name" value="{{ old('name') }}" required>
                <span class="field-hint">Nom affiché dans l’équipe et sur les écritures.</span>
            </div>
            <div class="field-group mb-2">
                <label for="member-phone">Téléphone, identifiant de connexion</label>
                <input id="member-phone" class="field" name="phone" value="{{ old('phone') }}" required>
                <span class="field-hint">C’est avec ce numéro que la personne ouvre son espace.</span>
            </div>
            <div class="field-group mb-2">
                <label for="member-email">Email</label>
                <input id="member-email" class="field" type="email" name="email" value="{{ old('email') }}">
                <span class="field-hint">Facultatif. La connexion se fait avec le téléphone.</span>
            </div>
            <div class="field-group mb-2">
                <label for="member-password">Mot de passe initial</label>
                <input id="member-password" class="field" type="password" name="password" required>
                <span class="field-hint">8 caractères minimum. Vous le transmettez vous-même. Il n’est plus affiché ensuite.</span>
            </div>
            <div class="field-group mb-2">
                <label for="role">Rôle</label>
                <select class="field" name="role" id="role">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                <span class="field-hint">Le rôle fixe les droits possibles. Vous pouvez en retirer ensuite.</span>
            </div>
            <p class="small text-muted">Si le téléphone existe déjà, le mot de passe actuel est conservé et le rôle est ajouté à cette organisation.</p>
            <div class="mb-3">
                @foreach ($permissions as $permission)
                    <label class="d-flex align-items-center gap-2 small mb-1" data-cap="{{ $permission->value }}">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->value }}">
                        {{ $permission->label() }}
                    </label>
                @endforeach
            </div>
            <button class="btn btn-primary">Créer l'accès</button>
        </form>
    </details>
    <div class="mt-4 space-y-3">
        @foreach ($members as $member)
            <article class="card">
                <p class="font-semibold mb-0">{{ $member->user->name }}</p>
                <p class="text-muted mb-0">{{ $member->role->label() }} · identifiant {{ $member->user->phone }}</p>
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
