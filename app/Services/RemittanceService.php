<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CashCollectionStatus;
use App\Enums\Permission;
use App\Enums\RemittanceStatus;
use App\Models\CashCollection;
use App\Models\CashRemittance;
use App\Models\CashRemittanceItem;
use App\Models\Organization;
use App\Models\User;
use App\Support\DomainException;
use Illuminate\Support\Facades\DB;

class RemittanceService
{
    public function __construct(
        private CounterService $counters,
        private AuditLogger $audit,
        private NotificationDispatcher $notifications,
    ) {}

    public function submit(Organization $organization, User $agent, string $currency): CashRemittance
    {
        return DB::transaction(function () use ($organization, $agent, $currency) {
            $collections = CashCollection::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('agent_id', $agent->id)
                ->where('currency', $currency)
                ->where('status', CashCollectionStatus::Confirmed)
                ->lockForUpdate()
                ->get();

            if ($collections->isEmpty()) {
                throw new DomainException('Aucune espèce à remettre dans cette devise.');
            }

            $amount = (int) $collections->sum('amount_minor');
            $remittance = CashRemittance::withoutGlobalScopes()->create([
                'organization_id' => $organization->id,
                'agent_id' => $agent->id,
                'reference' => $this->counters->reference($organization->id, 'remittance', 'REM'),
                'amount_minor' => $amount,
                'currency' => $currency,
                'status' => RemittanceStatus::Pending,
            ]);

            foreach ($collections as $collection) {
                CashRemittanceItem::withoutGlobalScopes()->create([
                    'organization_id' => $organization->id,
                    'cash_remittance_id' => $remittance->id,
                    'cash_collection_id' => $collection->id,
                    'amount_minor' => $collection->amount_minor,
                ]);
                $collection->status = CashCollectionStatus::RemittancePending;
                $collection->save();
            }

            $this->audit->log(
                $organization->id,
                $agent,
                'remittance.submitted',
                $remittance,
                'A remis '.money($amount, $currency).' au bailleur (en attente de confirmation).',
            );

            $this->notifications->notifyMembers(
                $organization,
                Permission::RemittancesConfirm,
                'remittance.submitted',
                'Remise d\'espèces',
                $agent->name.' déclare vous remettre '.money($amount, $currency).'.',
                route('office.remittances.show', $remittance),
            );

            return $remittance;
        });
    }

    public function confirm(CashRemittance $remittance, User $actor): CashRemittance
    {
        return DB::transaction(function () use ($remittance, $actor) {
            $remittance = CashRemittance::withoutGlobalScopes()->lockForUpdate()->findOrFail($remittance->id);

            if ($remittance->status !== RemittanceStatus::Pending) {
                throw new DomainException('Cette remise a déjà été traitée.');
            }

            if ((int) $remittance->agent_id === (int) $actor->id) {
                throw new DomainException('L\'agent ne peut pas confirmer sa propre remise.');
            }

            $remittance->status = RemittanceStatus::Confirmed;
            $remittance->confirmed_by = $actor->id;
            $remittance->confirmed_at = now();
            $remittance->save();

            $remittance->load('items.collection', 'agent');

            foreach ($remittance->items as $item) {
                $item->collection->status = CashCollectionStatus::Remitted;
                $item->collection->save();
            }

            $this->audit->log(
                $remittance->organization_id,
                $actor,
                'remittance.confirmed',
                $remittance,
                'A confirmé avoir reçu '.money((int) $remittance->amount_minor, $remittance->currency).' de '.$remittance->agent->name.'.',
            );

            $this->notifications->notify(
                $remittance->agent,
                'remittance.confirmed',
                'Remise confirmée',
                'Le bailleur a confirmé la réception de '.money((int) $remittance->amount_minor, $remittance->currency).'.',
            );

            return $remittance;
        });
    }

    public function reject(CashRemittance $remittance, User $actor, string $reason): CashRemittance
    {
        return DB::transaction(function () use ($remittance, $actor, $reason) {
            $remittance = CashRemittance::withoutGlobalScopes()->lockForUpdate()->findOrFail($remittance->id);

            if ($remittance->status !== RemittanceStatus::Pending) {
                throw new DomainException('Cette remise a déjà été traitée.');
            }

            $remittance->status = RemittanceStatus::Rejected;
            $remittance->rejection_reason = $reason;
            $remittance->confirmed_by = $actor->id;
            $remittance->confirmed_at = now();
            $remittance->save();
            $remittance->load('items.collection');

            foreach ($remittance->items as $item) {
                $item->collection->status = CashCollectionStatus::Confirmed;
                $item->collection->save();
            }

            $this->audit->log($remittance->organization_id, $actor, 'remittance.rejected', $remittance, 'A refusé la remise '.$remittance->reference.'. '.$reason);

            return $remittance;
        });
    }
}
