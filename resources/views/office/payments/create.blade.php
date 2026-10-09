@extends('layouts.shell')
@section('content')
    <x-page-header title="Déclarer un paiement" subtitle="Choisissez le client, puis la facture à régler." />
    @if ($tenants->isEmpty())
        <p class="text-muted mt-4">Aucun client pour le moment. Ajoutez un locataire avant d'enregistrer un paiement.</p>
    @else
        <form method="POST" action="{{ route('office.payments.store') }}" enctype="multipart/form-data" class="mt-4" id="declare-payment">
            @csrf
            <label class="small fw-semibold" for="client-filter">Rechercher un client
                <input id="client-filter" class="field mt-1" type="search" placeholder="Ex. Jean ou 0820000001" autocomplete="off">
            </label>
            <p class="field-hint mb-3">Le paiement est enregistré chez la personne choisie, sur sa facture ouverte.</p>

            <p class="small fw-semibold mb-2">Clients</p>
            <div class="client-picker" id="client-picker">
                @foreach ($tenants as $tenant)
                    @php
                        $rows = $invoices->where('tenant_id', $tenant->id);
                        $localPhone = str_starts_with($tenant->phone, '+243') ? '0'.substr($tenant->phone, 4) : $tenant->phone;
                    @endphp
                    <section class="client-picker-block" data-client="{{ mb_strtolower($tenant->name.' '.$tenant->phone.' '.$localPhone) }}">
                        <p class="fw-semibold mb-0">{{ $tenant->name }}</p>
                        <p class="text-muted small mb-0">{{ $tenant->phone }}</p>
                        @forelse ($rows as $invoice)
                            <label class="client-choice">
                                <input type="radio" name="invoice_id" value="{{ $invoice->id }}" data-balance="{{ number_format($invoice->balanceMinor() / 100, 2, '.', '') }}" @checked((string) old('invoice_id') === (string) $invoice->id) required>
                                <span>
                                    <strong>{{ $invoice->tenant->name }}</strong>
                                    · {{ $invoice->number }}
                                    · {{ $invoice->type->label() }}
                                    @if ($invoice->unit) · {{ $invoice->unit->name }} @endif
                                    · échéance {{ $invoice->due_on->format('d/m/Y') }}
                                    · reste {{ money($invoice->balanceMinor(), $invoice->currency) }}
                                </span>
                            </label>
                        @empty
                            <p class="text-muted small mt-2 mb-0">Aucune facture ouverte pour ce client.</p>
                        @endforelse
                    </section>
                @endforeach
            </div>

            <div class="mt-3">
                <x-payment-declare-fields prefix="payment" amount-placeholder="Ex. 150.00" amount-hint="Ex. 150.00. Après le choix du client, l'exemple devient le solde de sa facture." />
            </div>
            <button class="btn btn-primary w-100 mt-3" @disabled($invoices->isEmpty())>Déclarer</button>
            @if ($invoices->isEmpty())
                <p class="field-hint">Aucune facture ouverte. Le paiement se déclare sur une facture du client.</p>
            @endif
        </form>
        <script>
            (() => {
                const form = document.getElementById('declare-payment');
                const filter = document.getElementById('client-filter');
                const amount = document.getElementById('payment-amount');
                const blocks = [...form.querySelectorAll('[data-client]')];
                const radios = [...form.querySelectorAll('input[name="invoice_id"]')];

                const applyBalance = () => {
                    const chosen = radios.find((radio) => radio.checked);
                    if (chosen) {
                        amount.placeholder = 'Ex. ' + chosen.dataset.balance;
                    }
                };

                radios.forEach((radio) => {
                    radio.addEventListener('change', () => {
                        amount.placeholder = 'Ex. ' + radio.dataset.balance;
                    });
                });

                filter.addEventListener('input', () => {
                    const query = filter.value.trim().toLowerCase();
                    blocks.forEach((block) => {
                        block.hidden = query !== '' && !block.dataset.client.includes(query);
                    });
                });

                applyBalance();
            })();
        </script>
    @endif
@endsection
