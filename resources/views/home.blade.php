@extends('layouts.public')
@section('content')
    <section class="hero">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <p class="fw-bold text-uppercase small mb-2" style="color:#69468f; letter-spacing:.08em;">Logiciel pour bailleurs</p>
                    <h1>Gérez vos logements simplement avec Ma Résidence</h1>
                    <p class="lead mt-3 text-muted">Un espace isolé pour chaque bailleur : logements, locataires, contrats, loyers, paiements, dépenses et maintenance. Vos locataires suivent leur solde sans voir les autres dossiers.</p>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a class="btn btn-primary btn-lg" href="{{ route('register.landlord') }}">Commencer maintenant</a>
                        <a class="btn btn-ghost btn-lg" href="#solution">Découvrir la solution</a>
                    </div>
                    <div class="d-flex flex-wrap gap-3 mt-4 small text-muted">
                        <span><i class="bi bi-check2-circle text-brand" aria-hidden="true"></i> Multi-bailleurs</span>
                        <span><i class="bi bi-check2-circle text-brand" aria-hidden="true"></i> Encaissé distinct des factures</span>
                        <span><i class="bi bi-check2-circle text-brand" aria-hidden="true"></i> Mobile et ordinateur</span>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-panel p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <strong>Tableau de bord</strong>
                            <span class="mr-badge mr-badge-paid"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Ce mois</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-6"><div class="stat-card stat-green"><span class="label">Encaissé</span><span class="value">4 820 $</span></div></div>
                            <div class="col-6"><div class="stat-card stat-mauve"><span class="label">Attendu</span><span class="value">6 150 $</span></div></div>
                            <div class="col-6"><div class="stat-card stat-bad"><span class="label">Impayés</span><span class="value">1 330 $</span></div></div>
                            <div class="col-6"><div class="stat-card stat-info"><span class="label">Occupation</span><span class="value">92 %</span></div></div>
                        </div>
                        <div class="chart-bars mt-3" aria-hidden="true">
                            @foreach ([40, 55, 48, 70, 62, 80, 74, 88] as $height)
                                <div class="chart-col">
                                    <div class="chart-pair">
                                        <span class="bar-in" style="height: {{ $height }}px"></span>
                                        <span class="bar-out" style="height: {{ max(12, (int) ($height * 0.35)) }}px"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="solution" class="section-space">
        <div class="container">
            <h2 class="fw-bold mb-2">Tout le quotidien d'un bailleur</h2>
            <p class="text-muted mb-4">Les actions fréquentes restent à un ou deux clics : ajouter un logement, enregistrer un paiement, ouvrir une maintenance.</p>
            <div class="row g-3">
                @foreach ([
                    ['building', 'Gestion des logements', 'Résidences, appartements et chambres, avec statut, prix et historique.'],
                    ['people', 'Gestion des locataires', 'Fiches, occupants et contrats rattachés au bon logement.'],
                    ['receipt', 'Gestion des loyers', 'Factures, échéances, prorata et période de grâce.'],
                    ['cash-coin', 'Paiements', 'Espèces à double confirmation, virements, corrections sans effacer l\'historique.'],
                    ['tools', 'Maintenance', 'Signalement, suivi et coût, du locataire jusqu\'au technicien.'],
                    ['person-badge', 'Collaborateurs', 'Agents de recouvrement, techniciens et droits séparés.'],
                    ['graph-up-arrow', 'Rapports', 'Attendu, encaissé, dépenses et net, par période et par résidence.'],
                    ['bell', 'Notifications', 'Retards, paiements, messages, départs et annonces au même endroit.'],
                    ['shield-lock', 'Sécurité', 'Chaque bailleur ne voit que ses données. Les preuves restent privées.'],
                    ['buildings', 'Multi-résidences', 'Plusieurs propriétés dans le même espace, sans mélanger les comptes.'],
                ] as [$icon, $title, $text])
                    <div class="col-12 col-sm-6 col-lg-4">
                        <article class="feature-tile">
                            <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
                            <h3 class="h6 fw-bold">{{ $title }}</h3>
                            <p class="mb-0 text-muted">{{ $text }}</p>
                        </article>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-space bg-white border-top border-bottom">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <p class="mr-badge mr-badge-paid">1</p>
                    <h3 class="h5 mt-3">Ouvrez votre espace</h3>
                    <p class="text-muted">Créez le compte du bailleur. La plateforme reste pilotée par le Super Admin.</p>
                </div>
                <div class="col-md-4">
                    <p class="mr-badge mr-badge-maintenance">2</p>
                    <h3 class="h5 mt-3">Ajoutez les logements</h3>
                    <p class="text-muted">Résidences, loyers, locataires et collaborateurs restent dans la même organisation.</p>
                </div>
                <div class="col-md-4">
                    <p class="mr-badge mr-badge-progress">3</p>
                    <h3 class="h5 mt-3">Suivez l'argent réel</h3>
                    <p class="text-muted">Une facture due n'est pas un encaissement. Le net part des paiements validés.</p>
                </div>
            </div>
        </div>
    </section>

    @if ($units->isNotEmpty())
        <section class="section-space">
            <div class="container">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                    <h2 class="h3 fw-bold mb-0">Logements disponibles</h2>
                    <a class="fw-semibold" href="{{ route('catalog.index') }}">Voir le catalogue</a>
                </div>
                <div class="row g-3">
                    @foreach ($units as $unit)
                        <div class="col-12 col-md-6 col-lg-4">
                            <a class="card h-100" href="{{ route('catalog.index') }}">
                                <p class="small text-muted mb-1">{{ $unit->property->name }} · {{ $unit->property->city }}</p>
                                <h3 class="h5">{{ $unit->name }}</h3>
                                <p class="text-muted">{{ $unit->type->label() }}</p>
                                <strong>{{ money($unit->price_minor, $unit->currency) }}</strong>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section-space">
        <div class="container">
            <div class="hero-panel p-4 p-md-5 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="h3 fw-bold">Prêt à gérer votre parc ?</h2>
                    <p class="text-muted mb-0">Commencez avec votre première résidence, ou connectez-vous si votre espace existe déjà.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary" href="{{ route('register.landlord') }}">Commencer maintenant</a>
                    <a class="btn btn-ghost" href="{{ route('login') }}">Se connecter</a>
                </div>
            </div>
        </div>
    </section>
@endsection
