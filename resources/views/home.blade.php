@extends('layouts.public')
@section('document-title', 'Ma Résidence — Gestion locative')
@section('content')
    <section class="hero">
        <div class="container">
            <div class="row g-4 g-xl-5 align-items-center">
                <div class="col-lg-6 reveal">
                    <p class="kicker">Logiciel de gestion locative</p>
                    <h1>La gestion immobilière, avec une nouvelle exigence.</h1>
                    <p class="hero-lead mt-3 text-muted">Ma Résidence centralise les propriétés, les locataires, les contrats, les loyers, les dépenses et le suivi financier. Chaque bailleur travaille dans son propre espace.</p>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a class="btn btn-primary" href="{{ route('register.landlord') }}">Commencer maintenant</a>
                        <a class="btn btn-ghost" href="#fonctionnalites">Découvrir la plateforme</a>
                    </div>
                </div>
                <div class="col-lg-6 reveal reveal-2">
                    <figure class="hero-frame mb-0">
                        <img src="{{ asset('images/brand/residence-hero.jpg') }}" alt="Façade d'une résidence contemporaine" width="1800" height="2400">
                    </figure>
                </div>
            </div>
        </div>
    </section>

    <section id="plateforme" class="section-space">
        <div class="container">
            <div class="row g-4 align-items-end">
                <div class="col-lg-7 reveal">
                    <p class="kicker">La plateforme</p>
                    <h2>Un seul outil pour le patrimoine, l'occupation et l'argent réellement encaissé.</h2>
                </div>
                <div class="col-lg-5 reveal reveal-2">
                    <p class="text-muted mb-0">Les factures dues, les paiements validés et les dépenses restent distincts. Le net part des encaissements, pas des loyers simplement émis.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="fonctionnalites" class="section-space bg-white border-top border-bottom">
        <div class="container">
            <p class="kicker">Fonctionnalités principales</p>
            <h2 class="mb-4">Le quotidien du bailleur, sans détour.</h2>
            <div class="row g-3">
                @foreach ([
                    ['building', 'Biens', 'Résidences, appartements et chambres, avec statut et loyer.'],
                    ['people', 'Occupation', 'Locataires, occupants et contrats rattachés au bon logement.'],
                    ['cash-coin', 'Encaissements', 'Paiements validés, espèces à double confirmation, historique conservé.'],
                    ['receipt', 'Charges', 'Facturation des charges et dépenses rattachées à la période.'],
                    ['tools', 'Maintenance', 'Demandes suivies, du signalement jusqu\'au coût.'],
                    ['bell', 'Notifications', 'Retards, paiements, messages et annonces au même endroit.'],
                ] as [$icon, $feature, $text])
                    <div class="col-12 col-md-6 col-lg-4">
                        <article class="feature-tile">
                            <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
                            <h3 class="h6">{{ $feature }}</h3>
                            <p class="mb-0 text-muted">{{ $text }}</p>
                        </article>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="biens" class="section-space">
        <div class="container">
            <div class="story">
                <div class="story-copy reveal">
                    <p class="kicker">Biens et unités</p>
                    <h2>Multi-résidences, sans mélanger les comptes.</h2>
                    <p class="text-muted mt-3">Chaque propriété porte ses unités, ses loyers et son occupation. Un même bailleur peut suivre plusieurs résidences dans son espace, sans voir les dossiers des autres organisations.</p>
                </div>
                <figure class="story-media mb-0 reveal reveal-2">
                    <img src="{{ asset('images/brand/residence-facade.jpg') }}" alt="Immeuble contemporain aux lignes régulières" width="1400" height="933" loading="lazy">
                </figure>
            </div>
            @if ($units->isNotEmpty())
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mt-5 mb-3">
                    <h3 class="h4 mb-0">Logements actuellement proposés</h3>
                    <a class="fw-semibold" href="{{ route('catalog.index') }}">Voir le catalogue</a>
                </div>
                <div class="row g-3">
                    @foreach ($units as $unit)
                        <div class="col-12 col-md-6 col-lg-4">
                            <a class="card h-100" href="{{ route('catalog.index') }}">
                                <p class="small text-muted mb-1">{{ $unit->property->name }} · {{ $unit->property->city }}</p>
                                <h3 class="h5">{{ $unit->name }}</h3>
                                <p class="text-muted">{{ $unit->type->label() }}</p>
                                <strong class="tabular">{{ money($unit->price_minor, $unit->currency) }}</strong>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section id="locataires" class="section-space bg-white border-top border-bottom">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5">
                    <p class="kicker">Locataires et contrats</p>
                    <h2>Le bail reste attaché au logement.</h2>
                </div>
                <div class="col-lg-7">
                    <p class="text-muted">La fiche locataire, les occupants et le contrat vivent avec l'unité. Le locataire consulte son solde, ses factures et ses échanges. Il n'accède pas aux autres dossiers de la résidence.</p>
                    <p class="text-muted mb-0">Les collaborateurs — recouvrement, maintenance — n'obtiennent que les droits qui leur sont confiés.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="loyers" class="section-space">
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-lg-5">
                    <p class="kicker">Loyers, paiements et impayés</p>
                    <h2>Ce qui est dû n'est pas ce qui est encaissé.</h2>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <article class="feature-tile">
                                <h3 class="h6">Attendu</h3>
                                <p class="mb-0 text-muted">Les factures dont l'échéance tombe dans la période.</p>
                            </article>
                        </div>
                        <div class="col-md-4">
                            <article class="feature-tile">
                                <h3 class="h6">Encaissé</h3>
                                <p class="mb-0 text-muted">Les paiements approuvés, datés de leur validation.</p>
                            </article>
                        </div>
                        <div class="col-md-4">
                            <article class="feature-tile">
                                <h3 class="h6">En retard</h3>
                                <p class="mb-0 text-muted">Le solde encore ouvert après le délai de grâce.</p>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="charges" class="section-space bg-white border-top border-bottom">
        <div class="container">
            <p class="kicker">Charges et dépenses</p>
            <h2 class="mb-3">La facturation des charges reste séparée des dépenses du bailleur.</h2>
            <p class="text-muted mb-0 col-lg-8 px-0">Les charges peuvent être réparties par personne, par unité ou en montant fixe. Les dépenses validées entrent dans le net à leur date de dépense, une fois soustraites des encaissements.</p>
        </div>
    </section>

    <section id="maintenance" class="section-space">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-6">
                    <p class="kicker">Maintenance</p>
                    <h2 class="h3">Une demande, un suivi, un coût.</h2>
                    <p class="text-muted mb-0">Le locataire signale. Le bailleur ou le technicien fait avancer le dossier. Le coût reste visible dans le suivi du logement.</p>
                </div>
                <div class="col-md-6">
                    <p class="kicker">Notifications</p>
                    <h2 class="h3">Les événements importants au même endroit.</h2>
                    <p class="text-muted mb-0">Retards, paiements acceptés ou refusés, messages, remises et annonces apparaissent dans la cloche, avec un état lu ou non lu.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="pilotage" class="section-space bg-white border-top border-bottom">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-5">
                    <p class="kicker">Tableau de bord</p>
                    <h2>Les indicateurs que le bailleur consulte vraiment.</h2>
                    <p class="text-muted">Filtres de période et de résidence, puis la lecture financière : attendu, encaissé, dépenses, net, à recouvrer et en retard. L'aperçu ci-contre décrit ces indicateurs. Il ne présente pas de résultats commerciaux.</p>
                </div>
                <div class="col-lg-7">
                    <dl class="metric-list">
                        <div><dt>Revenus attendus</dt><dd>Factures échues sur la période.</dd></div>
                        <div><dt>Revenus encaissés</dt><dd>Paiements approuvés.</dd></div>
                        <div><dt>Dépenses</dt><dd>Dépenses validées.</dd></div>
                        <div><dt>Net</dt><dd>Encaissé moins dépenses.</dd></div>
                        <div><dt>À recouvrer</dt><dd>Soldes encore ouverts.</dd></div>
                        <div><dt>En retard</dt><dd>Après le délai de grâce.</dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </section>

    <section class="section-space">
        <div class="container">
            <p class="kicker">Pour les propriétaires et les gestionnaires</p>
            <h2 class="mb-4">Ce que l'espace change dans le travail.</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <article class="feature-tile">
                        <h3 class="h6">Isolation</h3>
                        <p class="mb-0 text-muted">Les données d'un bailleur ne sont pas visibles par un autre. Le Super Admin pilote la plateforme sans se substituer au bailleur.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="feature-tile">
                        <h3 class="h6">Lecture financière</h3>
                        <p class="mb-0 text-muted">Le tableau de bord sépare ce qui est facturé, ce qui est encaissé et ce qui a été dépensé.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="feature-tile">
                        <h3 class="h6">Actions courtes</h3>
                        <p class="mb-0 text-muted">Ajouter un logement, enregistrer un paiement ou ouvrir une maintenance reste accessible depuis l'espace de travail.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-5">
        <div class="container">
            <div class="cta-band d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="h3 mb-2">Ouvrir un espace bailleur</h2>
                    <p class="mb-0">Créez votre organisation, ou connectez-vous si elle existe déjà.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-light" href="{{ route('register.landlord') }}">Commencer maintenant</a>
                    <a class="btn btn-outline-light" href="{{ route('login') }}">Se connecter</a>
                </div>
            </div>
        </div>
    </section>
@endsection
