<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AllocationMethod;
use App\Enums\ContractStatus;
use App\Enums\InvoiceType;
use App\Enums\Permission;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use App\Models\UtilityCharge;
use App\Services\Billing\BillingCalendar;
use App\Support\DomainException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class UtilityService
{
    public function __construct(
        private BillingService $billing,
        private AuditLogger $audit,
        private NotificationDispatcher $notifications,
        private BillingCalendar $calendar,
    ) {}

    public function allocate(
        Organization $organization,
        Property $property,
        User $actor,
        InvoiceType $type,
        CarbonImmutable $month,
        int $totalMinor,
        string $currency,
        AllocationMethod $method,
    ): UtilityCharge {
        if (! in_array($type, [InvoiceType::Water, InvoiceType::Electricity], true)) {
            throw new DomainException('Seules l\'eau et l\'électricité se répartissent ici.');
        }

        if ($totalMinor <= 0) {
            throw new DomainException('Le montant global doit être positif.');
        }

        return DB::transaction(function () use ($organization, $property, $actor, $type, $month, $totalMinor, $currency, $method) {
            $periodKey = $month->format('Y-m');
            $exists = UtilityCharge::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('property_id', $property->id)
                ->where('type', $type->value)
                ->where('period_key', $periodKey)
                ->exists();

            if ($exists) {
                throw new DomainException('Cette charge existe déjà pour la période.');
            }

            $periodStart = $month->startOfMonth();
            $periodEnd = $month->endOfMonth();

            $contracts = Contract::withoutGlobalScopes()
                ->with('tenant', 'unit')
                ->where('organization_id', $organization->id)
                ->where('property_id', $property->id)
                ->whereIn('status', [ContractStatus::Active->value, ContractStatus::MoveOutRequested->value])
                ->whereDate('start_date', '<=', $periodEnd->toDateString())
                ->where(function ($query) use ($periodStart) {
                    $query->whereNull('end_date')->orWhereDate('end_date', '>=', $periodStart->toDateString());
                })
                ->orderBy('id')
                ->get();

            if ($contracts->isEmpty()) {
                throw new DomainException('Aucun logement occupé sur cette propriété pour la période.');
            }

            $shares = $this->shares($contracts->all(), $totalMinor, $method);
            $recordedTotal = array_sum($shares);
            $due = $this->calendar->dueOn($month, (int) $organization->preference('due_day'));

            $charge = UtilityCharge::withoutGlobalScopes()->create([
                'organization_id' => $organization->id,
                'property_id' => $property->id,
                'type' => $type,
                'period_key' => $periodKey,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'total_minor' => $recordedTotal,
                'currency' => $currency,
                'method' => $method,
                'created_by' => $actor->id,
            ]);

            foreach ($contracts as $index => $contract) {
                $amount = $shares[$index];
                if ($amount <= 0) {
                    continue;
                }

                $people = max(1, (int) $contract->tenant->occupants);
                $invoice = $this->billing->issueCharge(
                    $contract,
                    $type,
                    $periodKey,
                    $periodStart,
                    $periodEnd,
                    $amount,
                    $currency,
                    $due,
                    $type->label().' '.$periodKey,
                    $charge->id,
                    ['people' => $people, 'method' => $method->value],
                    $type->value.':'.$periodKey,
                );

                if ($contract->tenant->user) {
                    $this->notifications->notify(
                        $contract->tenant->user,
                        'invoice.'.$type->value,
                        'Facture '.$type->label(),
                        'Votre facture '.$type->label().' de '.money($amount, $currency).' est disponible.',
                        route('portal.invoices.show', $invoice),
                    );
                }
            }

            $this->audit->log(
                $organization->id,
                $actor,
                'utility.allocated',
                $charge,
                'A réparti '.money($recordedTotal, $currency).' de '.$type->label().' pour '.$property->name.'.',
            );

            $this->notifications->notifyMembers(
                $organization,
                Permission::InvoicesView,
                'utility.allocated',
                'Charge répartie',
                $type->label().' de '.money($recordedTotal, $currency).' répartie sur '.$property->name.'.',
            );

            return $charge;
        });
    }

    /**
     * @param  list<Contract>  $contracts
     * @return list<int>
     */
    private function shares(array $contracts, int $totalMinor, AllocationMethod $method): array
    {
        if ($method === AllocationMethod::Fixed) {
            return array_fill(0, count($contracts), $totalMinor);
        }

        if ($method === AllocationMethod::PerUnit) {
            return $this->split($totalMinor, array_fill(0, count($contracts), 1));
        }

        $weights = [];
        foreach ($contracts as $contract) {
            $weights[] = max(1, (int) $contract->tenant->occupants);
        }

        return $this->split($totalMinor, $weights);
    }

    /**
     * Les centimes restants sont attribués aux premières parts, une unité à la fois.
     *
     * @param  list<int>  $weights
     * @return list<int>
     */
    private function split(int $total, array $weights): array
    {
        $people = array_sum($weights);

        if ($people <= 0) {
            throw new DomainException('Aucune personne à facturer.');
        }

        $base = intdiv($total, $people);
        $extra = $total % $people;
        $amounts = [];

        foreach ($weights as $weight) {
            $bonus = min($weight, $extra);
            $amounts[] = ($weight * $base) + $bonus;
            $extra -= $bonus;
        }

        if (array_sum($amounts) !== $total) {
            throw new DomainException('La répartition n\'est pas équilibrée.');
        }

        return $amounts;
    }
}
