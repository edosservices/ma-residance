<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\ExpenseStatus;
use App\Enums\UnitStatus;
use App\Models\CashCollection;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function __construct(private BillingService $billing) {}

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

        return [
            'period' => $period,
            'period_label' => $label,
            'stock' => $this->stock($organization),
            'currencies' => $this->financials($organization, $start, $end),
            'held' => $this->held($organization),
            'overdue' => $this->overdueList($organization),
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
        $rentable = max(1, $total - $unavailable);

        return [
            'properties' => Property::withoutGlobalScopes()->where('organization_id', $organization->id)->where('status', 'active')->count(),
            'units' => $total,
            'occupied' => $occupied,
            'free' => $free,
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
     * @return array<string, array<string, int>>
     */
    public function financials(Organization $organization, ?CarbonImmutable $start, ?CarbonImmutable $end): array
    {
        $invoices = DB::table('invoices')
            ->where('organization_id', $organization->id)
            ->where('status', '!=', 'cancelled');

        if ($start && $end) {
            $invoices->whereBetween('period_start', [$start->toDateString(), $end->toDateString()]);
        }

        $rows = $invoices->get(['id', 'type', 'currency', 'amount_minor', 'status']);
        $result = [];

        foreach ($rows as $row) {
            $currency = $row->currency;
            $result[$currency] ??= $this->emptyMoney();
            $paid = $this->billing->netPaid((int) $row->id);
            $balance = max(0, (int) $row->amount_minor - $paid);
            $result[$currency]['expected'] += (int) $row->amount_minor;
            $result[$currency]['collected'] += min((int) $row->amount_minor, $paid);
            $result[$currency]['outstanding'] += $balance;
            $result[$currency][$row->type.'_expected'] = ($result[$currency][$row->type.'_expected'] ?? 0) + (int) $row->amount_minor;
            $result[$currency][$row->type.'_collected'] = ($result[$currency][$row->type.'_collected'] ?? 0) + min((int) $row->amount_minor, $paid);

            if ($row->status === 'overdue') {
                $result[$currency]['overdue'] += $balance;
            }
        }

        $expenses = Expense::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('status', ExpenseStatus::Recorded);

        if ($start && $end) {
            $expenses->whereBetween('spent_on', [$start->toDateString(), $end->toDateString()]);
        }

        foreach ($expenses->get(['currency', 'amount_minor']) as $expense) {
            $result[$expense->currency] ??= $this->emptyMoney();
            $result[$expense->currency]['expenses'] += (int) $expense->amount_minor;
        }

        foreach ($result as $currency => $bucket) {
            $result[$currency]['net'] = $bucket['collected'] - $bucket['expenses'];
            $result[$currency]['collection_rate'] = $bucket['expected'] > 0
                ? round($bucket['collected'] / $bucket['expected'] * 100, 1)
                : 0;
        }

        ksort($result);

        return $result;
    }

    /**
     * @return list<array{agent: string, currency: string, amount: int}>
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

    public function unitPerformance(Unit $unit, string $period = 'all'): array
    {
        $organization = Organization::query()->findOrFail($unit->organization_id);
        [$start, $end, $label] = $this->range($organization, $period);

        $invoices = Invoice::withoutGlobalScopes()
            ->where('unit_id', $unit->id)
            ->where('status', '!=', 'cancelled');

        if ($start && $end) {
            $invoices->whereBetween('period_start', [$start->toDateString(), $end->toDateString()]);
        }

        $collected = 0;
        $expected = 0;

        foreach ($invoices->get() as $invoice) {
            $expected += (int) $invoice->amount_minor;
            $collected += min((int) $invoice->amount_minor, $this->billing->netPaid($invoice->id));
        }

        $expenses = Expense::withoutGlobalScopes()
            ->where('unit_id', $unit->id)
            ->where('status', ExpenseStatus::Recorded);

        if ($start && $end) {
            $expenses->whereBetween('spent_on', [$start->toDateString(), $end->toDateString()]);
        }

        $expenseTotal = (int) $expenses->sum('amount_minor');
        $months = Contract::withoutGlobalScopes()
            ->where('unit_id', $unit->id)
            ->whereNotIn('status', [ContractStatus::Cancelled->value, ContractStatus::Draft->value])
            ->get()
            ->sum(function (Contract $contract) {
                $from = CarbonImmutable::parse($contract->start_date)->startOfMonth();
                $to = CarbonImmutable::parse($contract->terminated_at ?? $contract->end_date ?? now())->startOfMonth();

                return max(1, (int) $from->diffInMonths($to, true) + 1);
            });

        return [
            'label' => $label,
            'expected' => $expected,
            'collected' => $collected,
            'expenses' => $expenseTotal,
            'net' => $collected - $expenseTotal,
            'months' => $months,
            'maintenance' => MaintenanceRequest::withoutGlobalScopes()->where('unit_id', $unit->id)->count(),
            'currency' => $unit->currency,
        ];
    }

    /**
     * @return list<array{label: string, collected: int, expenses: int, net: int}>
     */
    public function monthlySeries(Organization $organization, string $currency): array
    {
        $now = CarbonImmutable::now($organization->timezone)->startOfMonth();
        $series = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = $now->subMonths($i);
            $bucket = $this->financials($organization, $month->startOfMonth(), $month->endOfMonth());
            $data = $bucket[$currency] ?? $this->emptyMoney();
            $series[] = [
                'label' => $month->translatedFormat('M'),
                'collected' => $data['collected'],
                'expenses' => $data['expenses'],
                'net' => $data['collected'] - $data['expenses'],
            ];
        }

        return $series;
    }

    public function propertyBreakdown(Organization $organization, ?CarbonImmutable $start, ?CarbonImmutable $end): array
    {
        $properties = Property::withoutGlobalScopes()->where('organization_id', $organization->id)->get();
        $rows = [];

        foreach ($properties as $property) {
            $invoices = Invoice::withoutGlobalScopes()
                ->where('property_id', $property->id)
                ->where('status', '!=', 'cancelled');

            if ($start && $end) {
                $invoices->whereBetween('period_start', [$start->toDateString(), $end->toDateString()]);
            }

            $collected = 0;
            foreach ($invoices->get() as $invoice) {
                $collected += min((int) $invoice->amount_minor, $this->billing->netPaid($invoice->id));
            }

            $expenses = Expense::withoutGlobalScopes()
                ->where('property_id', $property->id)
                ->where('status', ExpenseStatus::Recorded);

            if ($start && $end) {
                $expenses->whereBetween('spent_on', [$start->toDateString(), $end->toDateString()]);
            }

            $expenseTotal = (int) $expenses->sum('amount_minor');
            $rows[] = [
                'name' => $property->name,
                'collected' => $collected,
                'expenses' => $expenseTotal,
                'net' => $collected - $expenseTotal,
            ];
        }

        return $rows;
    }

    public function expensesByCategory(Organization $organization, ?CarbonImmutable $start, ?CarbonImmutable $end)
    {
        $query = Expense::withoutGlobalScopes()
            ->selectRaw('expense_category_id, currency, SUM(amount_minor) as total')
            ->where('organization_id', $organization->id)
            ->where('status', ExpenseStatus::Recorded)
            ->groupBy('expense_category_id', 'currency');

        if ($start && $end) {
            $query->whereBetween('spent_on', [$start->toDateString(), $end->toDateString()]);
        }

        return $query->with('category')->get();
    }

    private function overdueList(Organization $organization)
    {
        return Invoice::withoutGlobalScopes()
            ->with('tenant', 'unit')
            ->withBalance()
            ->where('organization_id', $organization->id)
            ->where('status', 'overdue')
            ->orderBy('due_on')
            ->limit(8)
            ->get();
    }

    /**
     * @return array<string, int|float>
     */
    private function emptyMoney(): array
    {
        return [
            'expected' => 0,
            'collected' => 0,
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
