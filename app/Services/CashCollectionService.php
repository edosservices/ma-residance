<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CashCollectionStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\Permission;
use App\Models\CashCollection;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Support\DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashCollectionService
{
    public function __construct(
        private PaymentService $payments,
        private CounterService $counters,
        private AuditLogger $audit,
        private NotificationDispatcher $notifications,
    ) {}

    public function initiate(
        Organization $organization,
        Invoice $invoice,
        User $actor,
        User $agent,
        int $amountMinor,
        bool $actorIsTenant,
    ): CashCollection {
        return DB::transaction(function () use ($organization, $invoice, $actor, $agent, $amountMinor, $actorIsTenant) {
            $invoice = Invoice::withoutGlobalScopes()->lockForUpdate()->findOrFail($invoice->id);

            if ($invoice->organization_id !== $organization->id || $invoice->status === InvoiceStatus::Cancelled) {
                throw new DomainException('Cette facture ne peut pas être encaissée.');
            }

            $agentMember = OrganizationMember::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('user_id', $agent->id)
                ->where('status', 'active')
                ->first();

            if ($agentMember === null || ! $agentMember->hasPermission(Permission::CollectionsRecord)) {
                throw new DomainException('Cet agent ne peut pas encaisser.');
            }

            $balance = app(BillingService::class)->balanceOf($invoice);

            if ($amountMinor <= 0 || $amountMinor > $balance) {
                throw new DomainException('Le montant dépasse le solde de la facture.');
            }

            $open = CashCollection::withoutGlobalScopes()
                ->where('invoice_id', $invoice->id)
                ->where('status', CashCollectionStatus::AwaitingConfirmation)
                ->exists();

            if ($open) {
                throw new DomainException('Un encaissement est déjà en cours pour cette facture.');
            }

            $collection = CashCollection::withoutGlobalScopes()->create([
                'organization_id' => $organization->id,
                'tenant_id' => $invoice->tenant_id,
                'agent_id' => $agent->id,
                'invoice_id' => $invoice->id,
                'reference' => $this->counters->reference($organization->id, 'collection', 'CSH'),
                'code' => strtoupper(Str::random(6)),
                'amount_minor' => $amountMinor,
                'currency' => $invoice->currency,
                'status' => CashCollectionStatus::AwaitingConfirmation,
                'tenant_confirmed_at' => $actorIsTenant ? now() : null,
                'agent_confirmed_at' => $actorIsTenant ? null : now(),
            ]);

            $this->audit->log(
                $organization->id,
                $actor,
                'collection.started',
                $collection,
                'A ouvert l\'encaissement '.$collection->code.' de '.money($amountMinor, $invoice->currency).'.',
            );

            $this->finalizeIfReady($collection, $actor);

            return $collection->refresh();
        });
    }

    public function confirm(CashCollection $collection, User $actor, bool $asTenant): CashCollection
    {
        return DB::transaction(function () use ($collection, $actor, $asTenant) {
            $collection = CashCollection::withoutGlobalScopes()->lockForUpdate()->findOrFail($collection->id);

            if ($collection->status !== CashCollectionStatus::AwaitingConfirmation) {
                throw new DomainException('Cet encaissement n\'est plus en attente de confirmation.');
            }

            if ($asTenant) {
                $collection->tenant_confirmed_at = $collection->tenant_confirmed_at ?? now();
            } else {
                if ((int) $collection->agent_id !== (int) $actor->id) {
                    throw new DomainException('Seul l\'agent désigné peut confirmer cet encaissement.');
                }

                $collection->agent_confirmed_at = $collection->agent_confirmed_at ?? now();
            }

            $collection->save();
            $collection->load('tenant', 'agent');

            $who = $asTenant ? $collection->tenant->name : $collection->agent->name;
            $this->audit->log(
                $collection->organization_id,
                $actor,
                'collection.confirmed_party',
                $collection,
                'A confirmé avoir '.($asTenant ? 'remis' : 'reçu').' '.money((int) $collection->amount_minor, $collection->currency).' ('.$who.').',
            );

            $this->finalizeIfReady($collection, $actor);

            return $collection->refresh();
        });
    }

    public function cancel(CashCollection $collection, User $actor): CashCollection
    {
        return DB::transaction(function () use ($collection, $actor) {
            $collection = CashCollection::withoutGlobalScopes()->lockForUpdate()->findOrFail($collection->id);

            if ($collection->status !== CashCollectionStatus::AwaitingConfirmation) {
                throw new DomainException('Cet encaissement ne peut plus être annulé.');
            }

            $collection->status = CashCollectionStatus::Cancelled;
            $collection->save();
            $this->audit->log($collection->organization_id, $actor, 'collection.cancelled', $collection, 'A annulé l\'encaissement '.$collection->code.'.');

            return $collection;
        });
    }

    private function finalizeIfReady(CashCollection $collection, User $actor): void
    {
        if ($collection->tenant_confirmed_at === null || $collection->agent_confirmed_at === null) {
            return;
        }

        if ($collection->status !== CashCollectionStatus::AwaitingConfirmation) {
            return;
        }

        $invoice = Invoice::withoutGlobalScopes()->findOrFail($collection->invoice_id);
        $organization = Organization::query()->findOrFail($collection->organization_id);
        $payment = $this->payments->capture(
            $organization,
            $invoice,
            $actor,
            (int) $collection->amount_minor,
            PaymentMethod::Cash,
            'Encaissement '.$collection->code,
        );

        $collection->payment_id = $payment->id;
        $collection->status = CashCollectionStatus::Confirmed;
        $collection->save();

        $collection->load('tenant', 'agent');
        $this->audit->log(
            $organization->id,
            $actor,
            'collection.confirmed',
            $collection,
            'A confirmé avoir reçu '.money((int) $collection->amount_minor, $collection->currency).' de '.$collection->tenant->name.'.',
        );

        $this->notifications->notifyMembers(
            $organization,
            Permission::PaymentsView,
            'collection.confirmed',
            'Espèces encaissées',
            $collection->agent->name.' détient '.money((int) $collection->amount_minor, $collection->currency).' reçus de '.$collection->tenant->name.'.',
            route('office.collections.show', $collection),
        );

        if ($collection->tenant->user) {
            $this->notifications->notify(
                $collection->tenant->user,
                'collection.confirmed',
                'Paiement espèces confirmé',
                'Votre paiement de '.money((int) $collection->amount_minor, $collection->currency).' est confirmé. Reçu '.$payment->reference.'.',
                route('portal.invoices.show', $invoice),
            );
        }
    }
}
