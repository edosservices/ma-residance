@extends('layouts.shell')
@section('content')
    <div class="flex items-start justify-between gap-2">
        <h1 class="text-2xl font-semibold">{{ $contract->reference }}</h1>
        <x-badge :tone="$contract->status->tone()">{{ $contract->status->label() }}</x-badge>
    </div>
    <p class="mt-2 text-sm text-muted">{{ $contract->tenant->name }} · {{ $contract->unit->property->name }} · {{ $contract->unit->name }}</p>
    <p class="mt-3 text-3xl font-semibold">{{ money($contract->rent_minor, $contract->currency) }}</p>
    @if ($contract->equivalent_minor)
        <p class="text-sm text-muted">Équivalent figé : {{ money($contract->equivalent_minor, $contract->equivalent_currency) }} au taux {{ $contract->fx_rate }}</p>
    @endif
    <p class="mt-2 text-sm">Du {{ $contract->start_date->format('d/m/Y') }} @if($contract->end_date) au {{ $contract->end_date->format('d/m/Y') }} @else · sans date de fin @endif</p>
    <p class="text-sm">Garantie {{ $contract->guaranteeDepositMonths() }} + {{ $contract->guaranteeAdvanceMonths() }} : {{ $contract->guaranteeDepositMonths() }} mois de caution et {{ $contract->guaranteeAdvanceMonths() }} mois d'avance.</p>
    @if ($contract->attachment_path)
        <p class="mt-2"><a href="{{ file_url($contract->attachment_path) }}">Voir la pièce jointe du contrat</a></p>
    @endif
    <p class="text-sm text-muted">Échéance le {{ $contract->due_day }}, grâce jusqu'au {{ $contract->grace_until_day }}. Prorata : {{ $prorata[$contract->prorata_method] ?? $contract->prorata_method }}</p>
    @if ($contract->conditions)
        <p class="card mt-3 text-sm">{{ $contract->conditions }}</p>
    @endif
    @if (allows('contracts.manage') && ! in_array($contract->status->value, ['ended', 'cancelled'], true))
        <form method="POST" action="{{ route('office.contracts.update', $contract) }}" class="card mt-4 space-y-2">
            @csrf
            @method('PUT')
            <p class="text-sm font-semibold">Conditions. Le loyer reste {{ money($contract->rent_minor, $contract->currency) }}.</p>
            <input class="field" type="date" name="end_date" value="{{ $contract->end_date?->format('Y-m-d') }}">
            <input class="field" type="number" name="generation_day" value="{{ $contract->generation_day }}">
            <input class="field" type="number" name="due_day" value="{{ $contract->due_day }}">
            <input class="field" type="number" name="grace_until_day" value="{{ $contract->grace_until_day }}">
            <select class="field" name="prorata_method">
                @foreach ($prorata as $code => $label)
                    <option value="{{ $code }}" @selected($contract->prorata_method === $code)>{{ $label }}</option>
                @endforeach
            </select>
            <textarea class="field" name="conditions">{{ $contract->conditions }}</textarea>
            <button class="btn btn-ghost w-full">Mettre à jour</button>
        </form>
    @endif
    <h2 class="mt-6 text-lg font-semibold">Acte de reconnaissance</h2>
    @if ($contract->recognitionDeed?->isCertified())
        @php $deed = $contract->recognitionDeed; @endphp
        <article class="card mt-2">
            <p class="kicker">Certifié</p>
            <p class="mb-1"><strong>{{ $deed->reference }}</strong> · {{ $deed->payee_name }} a reçu {{ money($deed->amount_minor, $deed->currency) }}</p>
            <p class="mb-1 text-sm">{{ $deed->deposit_months }} mois de garantie + {{ $deed->advance_months }} mois d'avance · {{ $deed->premises }}</p>
            <p class="mb-2 text-sm">Certificat {{ $deed->certificate_code }} · sceau {{ substr($deed->content_hash, 0, 16) }}</p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="{{ route('office.contracts.deed', $contract) }}">Voir la copie bailleur</a>
                <a class="btn btn-ghost" href="{{ route('office.contracts.deed.pdf', $contract) }}">Télécharger le PDF</a>
            </div>
        </article>
    @elseif (allows('contracts.manage'))
        @php $deed = $contract->recognitionDeed; @endphp
        <form method="POST" action="{{ route('office.contracts.deed.store', $contract) }}" class="card mt-2" enctype="multipart/form-data">
            @csrf
            <p class="mb-2">L'acte commence par le nom de celui qui a reçu l'argent. Le locataire recevra sa copie dès la certification.</p>
            @unless ($hasCertificate)
                <p class="field-hint">Avant de certifier, importez le certificat numérique dans les paramètres du bailleur.</p>
            @endunless
            <div class="field-group mb-2">
                <label for="payee_name">Nom de celui qui a reçu l'argent</label>
                <input id="payee_name" class="field" name="payee_name" value="{{ old('payee_name', $deed->payee_name ?? $deedDefaults['payee_name']) }}" required placeholder="Ex. Omar, bailleur">
                <span class="field-hint">Ex. Omar, bailleur. Changez ce nom si une autre personne a encaissé la garantie.</span>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <div class="field-group mb-2">
                        <label for="deposit_months">Mois de garantie</label>
                        <input id="deposit_months" class="field" type="number" name="deposit_months" min="0" max="24" value="{{ old('deposit_months', $deed->deposit_months ?? $deedDefaults['deposit_months']) }}" required>
                        <span class="field-hint">Ex. 3. Reprend la formule des paramètres, et peut être modifié pour cet acte.</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="field-group mb-2">
                        <label for="advance_months">Mois d'avance</label>
                        <input id="advance_months" class="field" type="number" name="advance_months" min="0" max="12" value="{{ old('advance_months', $deed->advance_months ?? $deedDefaults['advance_months']) }}" required>
                        <span class="field-hint">Ex. 1. Le total des mois est la somme des deux nombres.</span>
                    </div>
                </div>
            </div>
            <div class="field-group mb-2">
                <label for="identity_document">Pièce d'identité</label>
                <input id="identity_document" class="field" name="identity_document" value="{{ old('identity_document', $deed->identity_document ?? '') }}" placeholder="Ex. carte d'électeur 123456" maxlength="120">
                <span class="field-hint">Facultatif. Ex. carte d'électeur 123456, ou laissez vide.</span>
            </div>
            <div class="field-group mb-2">
                <label for="identity">Image de la pièce</label>
                <label class="field file-example" for="identity">
                    <input id="identity" type="file" name="identity" accept="image/*,.pdf">
                    <span data-file-label>Ex. photo de la pièce d'identité</span>
                </label>
                <span class="field-hint">Facultatif. Ex. photo ou scan de la pièce.</span>
            </div>
            <div class="field-group mb-2">
                <label for="origin">Origine</label>
                <input id="origin" class="field" name="origin" value="{{ old('origin', $deed->origin ?? '') }}" placeholder="Ex. Kinshasa, commune de Lemba" maxlength="160">
                <span class="field-hint">Ex. Kinshasa, commune de Lemba. D'où vient le locataire.</span>
            </div>
            <div class="field-group mb-2">
                <label for="premises">Lieu de prise de la maison</label>
                <input id="premises" class="field" name="premises" value="{{ old('premises', $deed->premises ?? $deedDefaults['premises']) }}" required maxlength="255" placeholder="Ex. Chez Omar · Appartement A · Avenue de la Gombe, Kinshasa">
                <span class="field-hint">Ex. Chez Omar · Appartement A · Avenue de la Gombe, Kinshasa.</span>
            </div>
            <div class="field-group mb-2">
                <label for="landlord_witnesses">Témoins côté bailleur</label>
                <input id="landlord_witnesses" class="field" name="landlord_witnesses" value="{{ old('landlord_witnesses', $deed->landlord_witnesses ?? '') }}" placeholder="Ex. Paul Kabila, Marie Nzeza" maxlength="500">
                <span class="field-hint">Ex. Paul Kabila, Marie Nzeza. Séparez les noms par une virgule.</span>
            </div>
            <div class="field-group mb-3">
                <label for="tenant_witnesses">Témoins côté locataire</label>
                <input id="tenant_witnesses" class="field" name="tenant_witnesses" value="{{ old('tenant_witnesses', $deed->tenant_witnesses ?? '') }}" placeholder="Ex. André Lumumba" maxlength="500">
                <span class="field-hint">Ex. André Lumumba. Séparez les noms par une virgule.</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-ghost" name="certify" value="0">Enregistrer sans certifier</button>
                <button class="btn btn-primary" name="certify" value="1">Certifier avec le certificat numérique</button>
            </div>
        </form>
    @else
        <p class="text-muted">L'acte n'est pas encore certifié.</p>
    @endif

    <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-muted">Factures</h2>
    <div class="mt-2 space-y-2">
        @foreach ($contract->invoices as $invoice)
            <a class="card block" href="{{ route('office.invoices.show', $invoice) }}">
                <p class="font-semibold">{{ $invoice->number }} · {{ $invoice->type->label() }}</p>
                <p class="text-sm">{{ money($invoice->amount_minor, $invoice->currency) }} · {{ $invoice->status->label() }}</p>
            </a>
        @endforeach
    </div>
@endsection
