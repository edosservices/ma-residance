@php
    $accountSettings = null;
    if (($shellRole ?? null) === 'admin') {
        $accountSettings = route('admin.settings');
    } elseif (($shellRole ?? null) === 'office' && allows('settings.manage')) {
        $accountSettings = route('office.settings.edit');
    }
@endphp
<div class="account-menu">
    <p class="account-kicker">Mon profil</p>
    <strong class="account-name">{{ $currentUser->name }}</strong>
    <span class="account-role">{{ $eyebrow ?? 'Compte' }}</span>
    @if ($currentUser->phone)
        <span class="account-role">{{ $currentUser->phone }}</span>
    @endif
    @if ($accountSettings)
        <a class="account-link" href="{{ $accountSettings }}"><i class="bi bi-gear" aria-hidden="true"></i> Paramètres</a>
    @endif
    <button type="button" class="account-link d-none" data-pwa-install><i class="bi bi-download" aria-hidden="true"></i> Installer l'application</button>
    <p class="account-role d-none" data-pwa-ios>Sur iPhone : Partager, puis « Sur l'écran d'accueil ».</p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-ghost w-100" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Se déconnecter</button>
    </form>
</div>
