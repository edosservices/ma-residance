@extends('layouts.shell')
@section('content')
    <x-page-header title="Comptes" subtitle="Accès à la plateforme" />
    <div class="table-responsive">
        <table class="table table-stack align-middle">
            <thead>
                <tr><th>Nom</th><th>Téléphone</th><th>Rôle</th><th>Statut</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td data-label="Nom"><strong>{{ $user->name }}</strong></td>
                        <td data-label="Téléphone">{{ $user->phone }}</td>
                        <td data-label="Rôle">{{ $user->is_super_admin ? 'Super Admin' : 'Utilisateur' }}</td>
                        <td data-label="Statut"><x-badge :tone="$user->status->value === 'active' ? 'good' : 'warn'">{{ $user->status->label() }}</x-badge></td>
                        <td data-label="Actions">
                            @unless ($user->is_super_admin)
                                <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                    @csrf
                                    <button class="btn btn-ghost btn-sm">{{ $user->status->value === 'active' ? 'Désactiver' : 'Activer' }}</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
@endsection
