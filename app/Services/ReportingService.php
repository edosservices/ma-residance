<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\ExpenseStatus;
use App\Enums\UnitStatus;
use App\Models\CashCollection;
use App\Models\CashRemittance;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Unit;
use App\Services\Billing\BillingCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function __construct(
        private BillingService $billing,
        private BillingCalendar $calendar,
    ) {}

    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable, 2: string}
     */
    public function range(Organization $organization, string $period): array
    {
        $now = CarbonImmutable::now($organization->timezone);

        return match ($period) {
            'prev_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth(), 'Mois précédent'],
            'year' => [$now->startOfYear(), $now->endOfYear(), 'Cette année'],
            'prev_year' => [$now->subYear()->startOfYear(), $now->subYear()->endOfYear(), 'Année précédente'],
            'all' => [null, null, 'Depuis le début'],
            default => [$now->startOfMonth(), $now->endOfMonth(), 'Ce mois'],
        };
    }

    public function dashboard(Organization $organization, string $period): array
    {
        $today = CarbonImmutable::now($organization->timezone)->startOfDay();
        $this->billing->refreshOpenInvoices($organization, $today);
        [$start, $end, $label] = $this->range($organization, $period);
        $late = $this->lateSnapshot($organization, $today);

        return [
            'period' => $period,
            'period_label' => $label,
            'stock' => $this->stock($organization),
            'currencies' => $this->financials($organization, $start, $end),
            'held' => $this->held($organization),
            'remitted' => $this->remitted($organization, $start, $end),
            'overdue' => $late['invoices'],
            'late_count' => $late['tenants'],
            'late_totals' => $late['totals'],
            'maintenance' => MaintenanceRequest::withoutGlobalScopes()
                ->with('unit')
                ->where('organization_id', $organization->id)
                ->whereNotIn('status', ['done', 'cancelled'])
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * @return array<string, int|float>
     */
    public function stock(Organization $organization): array
    {
        $units = Unit::withoutGlobalScopes()->where('organization_id', $organization->id);
        $total = (clone $units)->count();
        $unavailable = (clone $units)->where('status', UnitStatus::Unavailable)->count();
        $occupied = (clone $units)->whereIn('status', [UnitStatus::Occupied, UnitStatus::DepartureScheduled])->count();
        $free = (clone $units)->where('status', UnitStatus::Available)->count();
        $maintenance = (clone $units)->where('status', UnitStatus::Maintenance)->count();
        $rentable = max(1, $total - $unavailable);

        return [
            'properties' => Property::withoutGlobalScopes()->where('organization_id', $organization->id)->where('status', 'active')->count(),
            'units' => $total,
            'occupied' => $occupied,
            'free' => $free,
            'maintenance' => $maintenance,
            'tenants' => Contract::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->whereIn('status', [ContractStatus::Active->value, ContractStatus::MoveOutRequested->value])
                ->pluck('tenant_id')
                ->unique()
                ->count(),
            'occupancy_rate' => $total === 0 ? 0 : round($occupied / $rentable * 100, 1),
        ];
    }

    /**
     * Attendu = factures dues sur la période.
     * Encaissé = paiements validés dont la date de validation tombe dans la période.
     * Le net ne reprend jamais le montant d'une facture non encaissée.
     *
     * @return array<string, array<string, int|float>>
     */
    public function financials(Organization $organization, ?CarbonImmutable $start, ?CarbonImmutable $end, ?int $propertyId = null, ?int $unitId = null): array
    {
        $invoices = DB::table('invoices')
            ->where('organization_id', $organization->id)
            ->where('status', '!=', 'cancelled')
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereDate('due_on', '>=', $start->toDateString())
                    ->whereDate('due_on', '<=', $end->toDateString());
            })
            ->get(['id', 'type', 'currency', 'amount_minor', 'status']);

        $paid = $this->netPaidMap($invoices->pluck('id')->all());
        $result = [];

        foreach ($invoices as $row) {
            $currency = $row->currency;
            $result[$currency] ??= $this->emptyMoney();
            $net = $paid[(int) $row->id] ?? 0;
            $balance = max(0, (int) $row->amount_minor - $net);
            $settled = min((int) $row->amount_minor, max(0, $net));
            $result[$currency]['expected'] += (int) $row->amount_minor;
            $result[$currency]['outstanding'] += $balance;
            $result[$currency]['settled'] += $settled;
            $result[$currency][$row->type.'_expected'] = ($result[$currency][$row->type.'_expected'] ?? 0) + (int) $row->amount_minor;

            if ($row->status === 'overdue') {
                $result[$currency]['overdue'] += $balance;
            }
        }

        $collected = DB::table('payment_allocations as allocations')
            ->join('payments', 'payments.id', '=', 'allocations.payment_id')
            ->join('invoices', 'invoices.id', '=', 'allocations.invoice_id')
            ->where('payments.organization_id', $organization->id)
            ->where('payments.status', 'approved')
            ->when($propertyId, fn ($query) => $query->where('invoices.property_id', $propertyId))
            ->when($unitId, fn ($query) => $query->where('invoices.unit_id', $unitId))
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereDate('payments.reviewed_at', '>=', $start->toDateString())
                    ->whereDate('payments.reviewed_at', '<=', $end->toDateString());
            })
            ->groupBy('invoices.type', 'invoices.currency', 'payments.kind')
            ->get([
                'invoices.type',
                'invoices.currency',
                'payments.kind',
                DB::raw('SUM(allocations.amount_minor) as total'),
            ]);

        foreach ($collected as $row) {
            $result[$row->currency] ??= $this->emptyMoney();
            $signed = ($row->kind === 'reversal' ? -1 : 1) * (int) $row->total;
            $result[$row->currency]['collected'] += $signed;
            $key = $row->type.'_collected';
            $result[$row->currency][$key] = ($result[$row->currency][$key] ?? 0) + $signed;
        }

        $expenses = Expense::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('status', ExpenseStatus::Recorded)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId));

        if ($start && $end) {
            $expenses->whereDate('spent_on', '>=', $start->toDateString())
                ->whereDate('spent_on', '<=', $end->toDateString());
        }

        foreach ($expenses->get(['currency', 'amount_minor']) as $expense) {
            $result[$expense->currency] ??= $this->emptyMoney();
            $result[$expense->currency]['expenses'] += (int) $expense->amount_minor;
        }

        foreach ($result as $currency => $bucket) {
            $result[$currency]['net'] = $bucket['collected'] - $bucket['expenses'];
            $result[$currency]['collection_rate'] = $bucket['expected'] > 0
                ? round($bucket['settled'] / $bucket['expected'] * 100, 1)
                : 0;
        }

        ksort($result);

        return $result;
    }

    /**
     * @return list<array{agent: string, agent_id: int, currency: string, amount: int}>
     */
    public function held(Organization $organization): array
    {
        return CashCollection::withoutGlobalScopes()
            ->with('agent')
            ->where('organization_id', $organization->id)
            ->whereIn('status', ['confirmed', 'remittance_pending'])
            ->get()
            ->groupBy(fn ($row) => $row->agent_id.'|'.$row->currency)
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'agent' => $first->agent->name,
                    'agent_id' => $first->agent_id,
                    'currency' => $first->currency,
                    'amount' => (int) $rows->sum('amount_minor'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{currency: string, amount: int}>
     */
    public function remitted(Organization $organization, ?CarbonImmutable $start, ?CarbonImmutable $end): array
    {
        $query = CashRemittance::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('status', 'confirmed');

        if ($start && $end) {
            $query->whereDate('confirmed_at', '>=', $start->toDateString())
                ->whereDate('confirmed_at', '<=', $end->toDateString());
        }

        return $query->get(['currency', 'amount_minor'])
            ->groupBy('currency')
            ->map(fn ($rows, $currency) => [
                'currency' => $currency,
                'amount' => (int) $rows->sum('amount_minor'),
            ])
            ->values()
            ->all();
    }

    public function unitPerformance(Unit $unit, string $period = 'all'): array
    {
        $organization = Organization::query()->findOrFail($unit->organization_id);
        [$start, $end, $label] = $this->range($organization, $period);
        $money = $this->financials($organization, $start, $end, null, $unit->id);
        $bucket = $money[$unit->currency] ?? $this->emptyMoney();

        $months = Contract::withoutGlobalScopes()
            ->where('unit_id', $unit->id)
            ->whereNotIn('status', [ContractStatus::Cancelled->value, ContractStatus::Draft->value])
            ->get()
            ->sum(function (Contract $contract) {
                $from = CarbonImmutable::parse($contract->start_date)->startOfMonth();
                $to = CarbonImmutable::parse($contract->terminated_at ?? $contract->end_date ?? now())->startOfMonth();

                return max(1, (int) $from->diffInMonths($to, true) + 1);
            });

        $maintenanceCost = (int) Expense::withoutGlobalScopes()
            ->where('unit_id', $unit->id)
            ->where('status', ExpenseStatus::Recorded)
            ->where(function ($query) {
                $query->whereNotNull('maintenance_request_id')
                    ->orWhereHas('category', fn ($category) => $category->where('slug', 'maintenance'));
            })
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereDate('spent_on', '>=', $start->toDateString())
                    ->whereDate('spent_on', '<=', $end->toDateString());
            })
            ->sum('amount_minor');

        $payments = Payment::withoutGlobalScopes()
            ->with('invoice')
            ->where('status', 'approved')
            ->whereHas('invoice', fn ($query) => $query->where('unit_id', $unit->id))
            ->latest('reviewed_at')
            ->limit(8)
            ->get();

        return [
            'label' => $label,
            'expected' => $bucket['expected'],
            'collected' => $bucket['collected'],
            'expenses' => $bucket['expenses'],
            'net' => $bucket['net'],
            'outstanding' => $bucket['outstanding'],
            'overdue' => $bucket['overdue'],
            'months' => $months,
            'maintenance' => MaintenanceRequest::withoutGlobalScopes()->where('unit_id', $unit->id)->count(),
            'maintenance_cost' => $maintenanceCost,
            'currency' => $unit->currency,
            'payments' => $payments,
        ];
    }

    /**
     * @return list<array{label: string, collected: int, expenses: int, net: int}>
     */
    public function monthlySeries(Organization $organization, string $currency, ?int $propertyId = null): array
    {
        $now = CarbonImmutable::now($organization->timezone)->startOfMonth();
        $series = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = $now->subMonthsNoOverflow($i);
            $bucket = $this->financials($organization, $month->startOfMonth(), $month->endOfMonth(), $propertyId);
            $data = $bucket[$currency] ?? $this->emptyMoney();
            $series[] = [
                'label' => $month->translatedFormat('M'),
                'collected' => $data['collected'],
                'expenses' => $data['expenses'],
                'net' => $data['net'],
            ];
        }

        return $series;
    }

    public function propertyBreakdown(Organization $organization, ?CarbonImmutable $start, ?CarbonImmutable $end): array
    {
        $rows = [];

        foreach (Property::withoutGlobalScopes()->where('organization_id', $organization->id)->orderBy('name')->get() as $property) {
            $rows[] = [
                'id' => $property->id,
                'name' => $property->name,
                'currencies' => $this->financials($organization, $start, $end, $property->id),
            ];
        }

        return $rows;
    }

    public function expensesByCategory(Organization $organization, ?CarbonImmutable $start, ?CarbonImmutable $end, ?int $propertyId = null)
    {
        $query = Expense::withoutGlobalScopes()
            ->selectRaw('expense_category_id, currency, SUM(amount_minor) as total')
            ->where('organization_id', $organization->id)
            ->where('status', ExpenseStatus::Recorded)
            ->when($propertyId, fn ($inner) => $inner->where('property_id', $propertyId))
            ->groupBy('expense_category_id', 'currency');

        if ($start && $end) {
            $query->whereDate('spent_on', '>=', $start->toDateString())
                ->whereDate('spent_on', '<=', $end->toDateString());
        }

        return $query->with('category')->get();
    }

    /**
     * @param  list<int>  $invoiceIds
     * @return array<int, int>
     */
    private function netPaidMap(array $invoiceIds): array
    {
        if ($invoiceIds === []) {
            return [];
        }

        return DB::table('payment_allocations')
            ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->whereIn('payment_allocations.invoice_id', $invoiceIds)
            ->where('payments.status', 'approved')
            ->groupBy('payment_allocations.invoice_id')
            ->selectRaw("payment_allocations.invoice_id as invoice_id, SUM(CASE WHEN payments.kind = 'payment' THEN payment_allocations.amount_minor WHEN payments.kind = 'reversal' THEN -payment_allocations.amount_minor ELSE 0 END) as net_paid")
            ->pluck('net_paid', 'invoice_id')
            ->map(fn ($amount) => (int) $amount)
            ->all();
    }

    /**
     * @return array{invoices: Collection, tenants: int, totals: array<string, int>}
     */
    private function lateSnapshot(Organization $organization, CarbonImmutable $today): array
    {
        $rows = Invoice::withoutGlobalScopes()
            ->with('tenant', 'unit', 'contract')
            ->withBalance()
            ->where('organization_id', $organization->id)
            ->where('status', 'overdue')
            ->orderBy('due_on')
            ->get();

        $totals = [];

        foreach ($rows as $invoice) {
            $grace = (int) ($invoice->contract?->grace_until_day ?? $organization->preference('grace_until_day'));
            $due = CarbonImmutable::parse($invoice->due_on)->startOfDay();
            $invoice->setAttribute('days_late', $this->calendar->daysLate($today, $due));
            $invoice->setAttribute('grace_label', $this->calendar->graceEndsOn($due, $grace)->format('d/m'));
            $totals[$invoice->currency] = ($totals[$invoice->currency] ?? 0) + $invoice->balanceMinor();
        }

        return [
            'invoices' => $rows->take(8),
            'tenants' => $rows->pluck('tenant_id')->unique()->count(),
            'totals' => $totals,
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function emptyMoney(): array
    {
        return [
            'expected' => 0,
            'collected' => 0,
            'settled' => 0,
            'outstanding' => 0,
            'overdue' => 0,
            'expenses' => 0,
            'net' => 0,
            'rent_expected' => 0,
            'rent_collected' => 0,
            'water_expected' => 0,
            'water_collected' => 0,
            'electricity_expected' => 0,
            'electricity_collected' => 0,
            'other_expected' => 0,
            'other_collected' => 0,
            'collection_rate' => 0,
        ];
    }
}
