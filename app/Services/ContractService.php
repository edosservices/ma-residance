<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\Permission;
use App\Enums\RentalRequestStatus;
use App\Enums\UnitStatus;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\RentalRequest;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\DomainException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ContractService
{
    public function __construct(
        private CounterService $counters,
        private ExchangeRateService $rates,
        private BillingService $billing,
        private TenantService $tenants,
        private AuditLogger $audit,
        private NotificationDispatcher $notifications,
    ) {}

    public function requestUnit(Unit $unit, User $user, ?string $message = null): RentalRequest
    {
        return DB::transaction(function () use ($unit, $user, $message) {
            $unit = Unit::withoutGlobalScopes()->lockForUpdate()->findOrFail($unit->id);

            if ($unit->status !== UnitStatus::Available) {
                throw new DomainException('Ce logement n\'est plus disponible.');
            }

            $unit->status = UnitStatus::Pending;
            $unit->save();

            $request = RentalRequest::withoutGlobalScopes()->create([
                'organization_id' => $unit->organization_id,
                'unit_id' => $unit->id,
                'user_id' => $user->id,
                'message' => $message,
                'status' => RentalRequestStatus::Pending,
            ]);

            $organization = Organization::query()->findOrFail($unit->organization_id);
            $this->notifications->notifyMembers(
                $organization,
                Permission::ContractsManage,
                'rental.requested',
                'Nouvelle demande',
                "{$user->name} demande le logement {$unit->name}.",
                route('office.requests.show', $request),
            );

            $this->audit->log($unit->organization_id, $user, 'rental.requested', $request, "A demandé le logement {$unit->reference}.");

            return $request;
        });
    }

    public function refuse(RentalRequest $request, User $actor, ?string $note = null): RentalRequest
    {
        return DB::transaction(function () use ($request, $actor, $note) {
            $request = RentalRequest::withoutGlobalScopes()->lockForUpdate()->findOrFail($request->id);

            if ($request->status !== RentalRequestStatus::Pending) {
                throw new DomainException('Cette demande a déjà été traitée.');
            }

            $request->status = RentalRequestStatus::Refused;
            $request->decided_by = $actor->id;
            $request->decided_at = now();
            $request->decision_note = $note;
            $request->save();

            Unit::withoutGlobalScopes()
                ->whereKey($request->unit_id)
                ->where('status', UnitStatus::Pending->value)
                ->update(['status' => UnitStatus::Available->value]);

            $request->load('user', 'unit');
            $this->notifications->notify(
                $request->user,
                'rental.refused',
                'Demande refusée',
                'Votre demande pour '.$request->unit->name.' a été refusée.',
            );
            $this->audit->log($request->organization_id, $actor, 'rental.refused', $request, 'A refusé une demande de location.');

            return $request;
        });
    }

    public function accept(RentalRequest $request, User $actor, array $terms): Contract
    {
        return DB::transaction(function () use ($request, $actor, $terms) {
            $request = RentalRequest::withoutGlobalScopes()->lockForUpdate()->findOrFail($request->id);

            if ($request->status !== RentalRequestStatus::Pending) {
                throw new DomainException('Cette demande a déjà été traitée.');
            }

            $organization = Organization::query()->findOrFail($request->organization_id);
            $unit = Unit::withoutGlobalScopes()->lockForUpdate()->findOrFail($request->unit_id);
            $tenant = $this->tenants->ensureForUser($organization, $request->user()->first());

            $contract = $this->open(
                $organization,
                $unit,
                $tenant,
                $actor,
                CarbonImmutable::parse($terms['start_date']),
                isset($terms['end_date']) && $terms['end_date'] ? CarbonImmutable::parse($terms['end_date']) : null,
                (int) $terms['rent_minor'],
                $terms['currency'],
                $terms['conditions'] ?? null,
                true,
            );

            $request->status = RentalRequestStatus::Accepted;
            $request->tenant_id = $tenant->id;
            $request->decided_by = $actor->id;
            $request->decided_at = now();
            $request->save();

            $this->notifications->notify(
                $request->user,
                'rental.accepted',
                'Demande acceptée',
                'Votre demande pour '.$unit->name.' a été acceptée.',
                route('portal.contract'),
            );

            return $contract;
        });
    }

    public function open(
        Organization $organization,
        Unit $unit,
        Tenant $tenant,
        User $actor,
        CarbonImmutable $start,
        ?CarbonImmutable $end,
        int $rentMinor,
        string $currency,
        ?string $conditions = null,
        bool $fromRequest = false,
    ): Contract {
        if ($rentMinor <= 0) {
            throw new DomainException('Le loyer doit être positif.');
        }

        if (! in_array($currency, $organization->currencies(), true)) {
            throw new DomainException('Cette devise n\'est pas activée.');
        }

        return DB::transaction(function () use ($organization, $unit, $tenant, $actor, $start, $end, $rentMinor, $currency, $conditions, $fromRequest) {
            $unit = Unit::withoutGlobalScopes()->lockForUpdate()->findOrFail($unit->id);

            $allowed = $fromRequest
                ? [UnitStatus::Pending, UnitStatus::Available, UnitStatus::Reserved]
                : [UnitStatus::Available];

            if (! in_array($unit->status, $allowed, true) || $unit->organization_id !== $organization->id || $tenant->organization_id !== $organization->id) {
                throw new DomainException('Ce logement ne peut pas être loué dans cet état.');
            }

            $overlap = Contract::withoutGlobalScopes()
                ->where('unit_id', $unit->id)
                ->whereNotIn('status', [ContractStatus::Ended->value, ContractStatus::Cancelled->value])
                ->exists();

            if ($overlap) {
                throw new DomainException('Un contrat existe déjà pour ce logement.');
            }

            $today = CarbonImmutable::now($organization->timezone)->startOfDay();
            $activeNow = $start->startOfDay()->lessThanOrEqualTo($today);
            $snapshot = $this->rates->snapshot($organization, $currency, $rentMinor);

            $contract = new Contract([
                'organization_id' => $organization->id,
                'property_id' => $unit->property_id,
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
                'reference' => $this->counters->reference($organization->id, 'contract', 'CTR'),
                'start_date' => $start->toDateString(),
                'end_date' => $end?->toDateString(),
                'rent_minor' => $rentMinor,
                'currency' => $currency,
                'exchange_rate_id' => $snapshot['exchange_rate_id'] ?? null,
                'fx_base_currency' => $snapshot['fx_base_currency'] ?? null,
                'fx_quote_currency' => $snapshot['fx_quote_currency'] ?? null,
                'fx_rate' => $snapshot['fx_rate'] ?? null,
                'equivalent_minor' => $snapshot['equivalent_minor'] ?? null,
                'equivalent_currency' => $snapshot['equivalent_currency'] ?? null,
                'billing_cycle' => 'monthly',
                'generation_day' => (int) $organization->preference('generation_day'),
                'due_day' => (int) $organization->preference('due_day'),
                'grace_until_day' => (int) $organization->preference('grace_until_day'),
                'prorata_method' => (string) $organization->preference('prorata_method'),
                'conditions' => $conditions,
                'status' => $activeNow ? ContractStatus::Active : ContractStatus::Pending,
                'activated_at' => $activeNow ? $today : null,
                'created_by' => $actor->id,
            ]);
            $contract->save();

            $unit->status = $activeNow ? UnitStatus::Occupied : UnitStatus::Reserved;
            $unit->save();

            if ($activeNow) {
                $this->billing->ensureInvoices($contract, $today);
            }

            $this->audit->log(
                $organization->id,
                $actor,
                'contract.created',
                $contract,
                "A créé le contrat {$contract->reference} pour {$tenant->name}.",
                ['rent_minor' => $rentMinor, 'currency' => $currency, 'fx_rate' => $contract->fx_rate],
            );

            $this->notifications->notifyMembers(
                $organization,
                Permission::ContractsView,
                'contract.created',
                'Nouveau contrat',
                "Le contrat {$contract->reference} est créé.",
                route('office.contracts.show', $contract),
            );

            return $contract;
        });
    }

    public function updateTerms(Contract $contract, User $actor, array $terms): Contract
    {
        if (in_array($contract->status, [ContractStatus::Ended, ContractStatus::Cancelled], true)) {
            throw new DomainException('Ce contrat est clos.');
        }

        $contract->end_date = $terms['end_date'] ?: null;
        $contract->conditions = $terms['conditions'] ?? $contract->conditions;
        $contract->due_day = (int) $terms['due_day'];
        $contract->generation_day = (int) $terms['generation_day'];
        $contract->grace_until_day = (int) $terms['grace_until_day'];
        $contract->prorata_method = $terms['prorata_method'];
        $contract->save();

        $this->audit->log($contract->organization_id, $actor, 'contract.updated', $contract, "A modifié les conditions du contrat {$contract->reference} sans changer le loyer.");

        return $contract;
    }
}
