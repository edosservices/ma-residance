@extends('layouts.shell')
@section('content')
    @if ($contract)
        <h1 class="text-2xl font-semibold">{{ $contract->reference }}</h1>
        <p class="mt-2">{{ $contract->unit->property->name }} · {{ $contract->unit->name }}</p>
        <p class="mt-3 text-3xl font-semibold">{{ money($contract->rent_minor, $contract->currency) }}</p>
        <p class="text-sm text-muted">Depuis le {{ $contract->start_date->format('d/m/Y') }}</p>
        <x-badge class="mt-3" :tone="$contract->status->tone()">{{ $contract->status->label() }}</x-badge>
        @if ($contract->conditions)
            <p class="card mt-4 text-sm">{{ $contract->conditions }}</p>
        @endif
        <p class="mt-3 text-sm">Garantie {{ $contract->guaranteeDepositMonths() }} + {{ $contract->guaranteeAdvanceMonths() }}.</p>
        @if ($contract->attachment_path)
            <p class="mt-2"><a href="{{ file_url($contract->attachment_path) }}">Voir la pièce jointe du contrat</a></p>
        @endif
        @if ($contract->recognitionDeed?->isCertified())
            <article class="card mt-4">
                <p class="kicker">Acte de reconnaissance</p>
                <p class="mb-1"><strong>{{ $contract->recognitionDeed->reference }}</strong></p>
                <p class="mb-2 text-sm">{{ $contract->recognitionDeed->payee_name }} a reçu {{ money($contract->recognitionDeed->amount_minor, $contract->recognitionDeed->currency) }} pour {{ $contract->recognitionDeed->months() }} mois.</p>
                <p class="mb-3 text-sm">Certificat {{ $contract->recognitionDeed->certificate_code }} · validé le {{ $contract->recognitionDeed->certified_at->timezone(config('app.timezone'))->format('d/m/Y') }}</p>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary" href="{{ route('portal.deed') }}">Voir ma copie</a>
                    <a class="btn btn-ghost" href="{{ route('portal.deed.pdf') }}">Télécharger le PDF</a>
                </div>
            </article>
        @endif
    @else
        <p class="card">Aucun contrat pour le moment.</p>
        <a class="btn btn-primary mt-4" href="{{ route('catalog.index') }}">Voir les logements</a>
    @endif
@endsection
