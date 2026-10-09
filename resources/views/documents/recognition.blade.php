@extends('layouts.print')
@section('document-title', 'Acte '.$deed->reference)
@section('content')
    <div class="actions">
        <button type="button" onclick="window.print()">Imprimer ou enregistrer en PDF</button>
        <a class="ghost" href="{{ $pdf }}">Télécharger le PDF</a>
    </div>
    <header class="banner">
        <h1>Acte de reconnaissance</h1>
        <p>{{ $copy === 'locataire' ? 'Copie locataire' : 'Copie bailleur' }} · {{ $deed->reference }}</p>
    </header>
    <div class="body">
        <p>Je soussigné(e) <strong>{{ $deed->payee_name }}</strong>, reconnais avoir reçu de <strong>{{ $deed->contract->tenant->name }}</strong> la somme de <strong>{{ money($deed->amount_minor, $deed->currency) }}</strong>.</p>
        <p>Cette somme couvre {{ $deed->deposit_months }} mois de garantie et {{ $deed->advance_months }} mois d'avance, soit {{ $deed->months() }} mois de loyer.</p>
        <h2>Locataire</h2>
        <p>{{ $deed->contract->tenant->name }}@if ($deed->contract->tenant->phone) · {{ $deed->contract->tenant->phone }}@endif</p>
        <p>Origine : {{ $deed->origin ?: 'Non précisée' }}</p>
        <p>Pièce d'identité : {{ $deed->identity_document ?: 'Non fournie' }}</p>
        @if ($deed->identity_path)
            <p><a href="{{ file_url($deed->identity_path) }}">Voir la pièce jointe</a></p>
        @endif
        <h2>Lieu de prise de la maison</h2>
        <p>{{ $deed->premises }}</p>
        <p>{{ $deed->contract->unit->property->name }} · {{ $deed->contract->unit->name }}</p>
        <h2>Témoins côté bailleur</h2>
        <p>{{ $deed->landlord_witnesses ?: 'Aucun témoin nommé' }}</p>
        <h2>Témoins côté locataire</h2>
        <p>{{ $deed->tenant_witnesses ?: 'Aucun témoin nommé' }}</p>
        <section class="seal">
            <p><strong>Certification</strong></p>
            <p>Acte validé le {{ $deed->certified_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}.</p>
            <p>Certificat numérique : {{ $deed->certificate_code }}</p>
            <p>Signataire : {{ $deed->certificate_holder }}</p>
            <p>Sceau : {{ substr($deed->content_hash, 0, 16) }}</p>
            <p>Ce sceau confirme que le bailleur a apposé son certificat numérique.</p>
            @if ($deed->certificate_path)
                <img class="sign" src="{{ file_url($deed->certificate_path) }}" alt="Certificat numérique de {{ $deed->certificate_holder }}">
            @endif
        </section>
    </div>
@endsection
