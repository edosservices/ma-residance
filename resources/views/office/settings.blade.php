@extends('layouts.shell')
@section('content')
    <h1 class="text-2xl font-semibold">Paramètres</h1>
    <form method="POST" action="{{ route('office.settings.update') }}" class="mt-4 space-y-2" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <label class="block text-sm">Jour de génération<input class="field" type="number" name="generation_day" min="1" max="28" value="{{ $organization->preference('generation_day') }}"></label>
        <label class="block text-sm">Jour d'échéance<input class="field" type="number" name="due_day" min="1" max="31" value="{{ $organization->preference('due_day') }}"></label>
        <label class="block text-sm">Grâce jusqu'au<input class="field" type="number" name="grace_until_day" min="1" max="28" value="{{ $organization->preference('grace_until_day') }}"></label>
        <label class="block text-sm">Prorata
            <select class="field" name="prorata_method">
                @foreach ($prorata as $code => $label)
                    <option value="{{ $code }}" @selected($organization->preference('prorata_method') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm">Rappel avant échéance (jours)<input class="field" type="number" name="reminder_days_before" value="{{ $organization->preference('reminder_days_before') }}"></label>
        <label class="block text-sm">Rappel de retard tous les (jours)<input class="field" type="number" name="reminder_repeat_days" value="{{ $organization->preference('reminder_repeat_days') }}"></label>
        <label class="block text-sm">Devise par défaut
            <select class="field" name="default_currency">
                @foreach ($organization->currencies() as $currency)
                    <option @selected($organization->preference('default_currency') === $currency)>{{ $currency }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="share_declaration_trace" value="1" @checked($organization->preference('share_declaration_trace'))>
            Montrer la traçabilité des déclarations au locataire
        </label>
        <p class="field-hint">Le détail reste chez le bailleur. Cochez pour l'afficher aussi dans l'espace locataire. Pour un collaborateur, utilisez le droit « Voir la traçabilité des déclarations ».</p>
        <h2 class="pt-3 text-lg font-semibold">Garantie locative</h2>
        <p class="field-hint">La formule 3 + 1 signifie 3 mois de caution et 1 mois payé d'avance. Changez les deux nombres pour une autre formule, par exemple 2 + 1.</p>
        <label class="block text-sm">Mois de caution
            <input class="field" type="number" name="guarantee_deposit_months" min="0" max="24" value="{{ old('guarantee_deposit_months', $organization->preference('guarantee_deposit_months')) }}" required>
            <span class="field-hint">Ex. 3. C'est le premier nombre de la formule.</span>
        </label>
        <label class="block text-sm">Mois payés d'avance
            <input class="field" type="number" name="guarantee_advance_months" min="0" max="12" value="{{ old('guarantee_advance_months', $organization->preference('guarantee_advance_months')) }}" required>
            <span class="field-hint">Ex. 1. C'est le second nombre. La formule affichée sera {{ (int) old('guarantee_deposit_months', $organization->preference('guarantee_deposit_months')) }} + {{ (int) old('guarantee_advance_months', $organization->preference('guarantee_advance_months')) }}.</span>
        </label>
        <h2 class="pt-3 text-lg font-semibold">Certificat numérique</h2>
        <p class="field-hint">Ce certificat est apposé sur l'acte de reconnaissance pour montrer qu'il est validé. Importez une image de signature ou de cachet, et le nom de celui qui signe.</p>
        <label class="block text-sm">Nom du signataire
            <input class="field" name="certificate_holder" value="{{ old('certificate_holder', $organization->preference('certificate_holder')) }}" maxlength="120" placeholder="Ex. Omar, bailleur de Chez Omar">
            <span class="field-hint">Ex. Omar, bailleur de Chez Omar. C'est le nom qui affirme l'acte.</span>
        </label>
        <label class="block text-sm">Image du certificat
            <input class="field" type="file" name="certificate" accept="image/*">
            <span class="field-hint">Ex. photo de la signature ou du cachet. Image JPG ou PNG.</span>
        </label>
        @if ($organization->preference('certificate_code'))
            <p class="text-sm">Certificat en place : <strong>{{ $organization->preference('certificate_code') }}</strong>@if ($organization->preference('certificate_holder')) · {{ $organization->preference('certificate_holder') }}@endif</p>
            @if ($organization->preference('certificate_path'))
                <img src="{{ file_url($organization->preference('certificate_path')) }}" alt="Certificat numérique du bailleur" style="max-width: 180px; max-height: 80px;">
            @endif
        @endif
        <button class="btn btn-primary w-full">Enregistrer</button>
    </form>
    <h2 class="mt-8 text-lg font-semibold">Taux de change</h2>
    <p class="text-sm text-muted">Un nouveau taux n'altère ni les contrats ni les paiements déjà enregistrés.</p>
    <form method="POST" action="{{ route('office.rates.store') }}" class="mt-3 grid grid-cols-3 gap-2">
        @csrf
        <input class="field" name="base_currency" value="USD" maxlength="3">
        <input class="field" name="quote_currency" value="CDF" maxlength="3">
        <input class="field" name="rate" placeholder="2900">
        <button class="btn btn-primary col-span-3">Enregistrer 1 base = taux</button>
    </form>
    <div class="mt-3 space-y-2">
        @foreach ($rates as $rate)
            <p class="text-sm">{{ $rate->effective_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · 1 {{ $rate->base_currency }} = {{ $rate->rate }} {{ $rate->quote_currency }}</p>
        @endforeach
    </div>
@endsection
