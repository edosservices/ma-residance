<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MoveOutStatus;
use App\Enums\Permission;
use App\Enums\UnitStatus;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\MoveOutRequest;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use App\Support\DomainException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class MoveOutService
{
    public function __construct(
        private BillingService $billing,
        private AuditLogger $audit,
        private NotificationDispatcher $notifications,
    ) {}

    public function request(Contract $contract, User $actor, CarbonImmutable $plannedOn, ?string $reason = null): MoveOutRequest
    {
        return DB::transaction(function () use ($contract, $actor, $plannedOn, $reason) {
            $contract = Contract::withoutGlobalScopes()->lockForUpdate()->findOrFail($contract->id);

            if (! in_array($contract->status, [ContractStatus::Active, ContractStatus::MoveOutRequested], true)) {
                throw new DomainException('Ce contrat ne permet pas un départ.');
            }

            $existing = MoveOutRequest::withoutGlobalScopes()
                ->where('contract_id', $contract->id)
                ->where('status', MoveOutStatus::Requested)
                ->exists();

            if ($existing) {
                throw new DomainException('Un départ est déjà en cours.');
            }

            $today = CarbonImmutable::now()->startOfDay();

            if ($plannedOn->startOfDay()->lessThan($today)) {
                throw new DomainException('La date de départ ne peut pas être passée.');
            }

            $moveOut = MoveOutRequest::withoutGlobalScopes()->create([
                'organization_id' => $contract->organization_id,
                'contract_id' => $contract->id,
                'tenant_id' => $contract->tenant_id,
                'requested_by' => $actor->id,
                'requested_on' => $today->toDateString(),
                'planned_on' => $plannedOn->toDateString(),
                'reason' => $reason,
                'status' => MoveOutStatus::Requested,
            ]);

            $contract->status = ContractStatus::MoveOutRequested;
            $contract->save();
            Unit::withoutGlobalScopes()->whereKey($contract->unit_id)->update(['status' => UnitStatus::DepartureScheduled->value]);

            $organization = Organization::query()->findOrFail($contract->organization_id);
            $contract->load('tenant', 'unit');
            $this->notifications->notifyMembers(
                $organization,
                Permission::MoveOutManage,
                'moveout.requested',
                'Départ demandé',
                $contract->tenant->name.' souhaite quitter '.$contract->unit->name.' le '.$plannedOn->format('d/m/Y').'.',
                route('office.moveouts.show', $moveOut),
            );
            $this->audit->log($organization->id, $actor, 'moveout.requested', $moveOut, 'A demandé à quitter le logement le '.$plannedOn->format('d/m/Y').'.');

            return $moveOut;
        });
    }

    public function complete(MoveOutRequest $moveOut, User $actor, array $checklist, ?string $note = null, ?string $photo = null): MoveOutRequest
    {
        foreach (['payments_checked', 'debts_checked', 'condition_checked', 'equipment_checked'] as $key) {
            if (empty($checklist[$key])) {
                throw new DomainException('La checklist de sortie doit être complète.');
            }
        }

        return DB::transaction(function () use ($moveOut, $actor, $checklist, $note, $photo) {
            $moveOut = MoveOutRequest::withoutGlobalScopes()->lockForUpdate()->findOrFail($moveOut->id);

            if ($moveOut->status !== MoveOutStatus::Requested) {
                throw new DomainException('Ce départ a déjà été traité.');
            }

            $contract = Contract::withoutGlobalScopes()->lockForUpdate()->findOrFail($moveOut->contract_id);
            $balance = $this->contractBalance($contract);

            if ($balance > 0) {
                throw new DomainException('Le locataire a encore un solde. Réglez les factures avant de libérer le logement.');
            }

            $moveOut->status = MoveOutStatus::Completed;
            $moveOut->checklist = $checklist;
            $moveOut->review_note = $note;
            $moveOut->photo_path = $photo;
            $moveOut->reviewed_by = $actor->id;
            $moveOut->completed_at = now();
            $moveOut->save();

            $contract->status = ContractStatus::Ended;
            $contract->terminated_at = now();
            $contract->end_date = $contract->end_date ?? $moveOut->planned_on;
            $contract->save();

            Unit::withoutGlobalScopes()->whereKey($contract->unit_id)->update(['status' => UnitStatus::Available->value]);

            $contract->load('tenant.user', 'unit');
            if ($contract->tenant->user) {
                $this->notifications->notify(
                    $contract->tenant->user,
                    'moveout.completed',
                    'Départ validé',
                    'Votre sortie de '.$contract->unit->name.' est validée.',
                );
            }

            $this->audit->log($contract->organization_id, $actor, 'moveout.completed', $moveOut, 'A validé la sortie et libéré '.$contract->unit->reference.'.');

            return $moveOut;
        });
    }

    public function reject(MoveOutRequest $moveOut, User $actor, string $note): MoveOutRequest
    {
        return DB::transaction(function () use ($moveOut, $actor, $note) {
            $moveOut = MoveOutRequest::withoutGlobalScopes()->lockForUpdate()->findOrFail($moveOut->id);

            if ($moveOut->status !== MoveOutStatus::Requested) {
                throw new DomainException('Ce départ a déjà été traité.');
            }

            $moveOut->status = MoveOutStatus::Rejected;
            $moveOut->review_note = $note;
            $moveOut->reviewed_by = $actor->id;
            $moveOut->save();

            $contract = Contract::withoutGlobalScopes()->findOrFail($moveOut->contract_id);
            $contract->status = ContractStatus::Active;
            $contract->save();
            Unit::withoutGlobalScopes()->whereKey($contract->unit_id)->update(['status' => UnitStatus::Occupied->value]);

            $this->audit->log($moveOut->organization_id, $actor, 'moveout.rejected', $moveOut, 'A refusé le départ. '.$note);

            return $moveOut;
        });
    }

    public function flagUpcoming(): void
    {
        Organization::query()->where('status', 'active')->each(function (Organization $organization) {
            $today = CarbonImmutable::now($organization->timezone)->startOfDay();
            $notice = (int) $organization->preference('move_out_notice_days', 3);
            $until = $today->addDays($notice);

            $requests = MoveOutRequest::withoutGlobalScopes()
                ->with('tenant', 'contract.unit')
                ->where('organization_id', $organization->id)
                ->where('status', MoveOutStatus::Requested)
                ->whereNull('reminder_sent_at')
                ->whereDate('planned_on', '<=', $until->toDateString())
                ->get();

            foreach ($requests as $request) {
                $this->notifications->notifyMembers(
                    $organization,
                    Permission::MoveOutManage,
                    'moveout.soon',
                    'Départ proche',
                    $request->tenant->name.' quitte '.$request->contract->unit->name.' le '.$request->planned_on->format('d/m/Y').'.',
                    route('office.moveouts.show', $request),
                );
                $request->reminder_sent_at = now();
                $request->save();
            }
        });
    }

    private function contractBalance(Contract $contract): int
    {
        $invoices = Invoice::withoutGlobalScopes()
            ->where('contract_id', $contract->id)
            ->where('status', '!=', InvoiceStatus::Cancelled->value)
            ->get();

        $total = 0;

        foreach ($invoices as $invoice) {
            $total += $this->billing->balanceOf($invoice);
        }

        return $total;
    }
}
