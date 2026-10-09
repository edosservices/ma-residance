<?php

declare(strict_types=1);

namespace App\Http\Controllers\Office;

use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Services\Billing\ProrataManager;
use App\Services\ReportingService;
use App\Support\CurrentContext;
use Carbon\CarbonImmutable;

class DashboardController extends Controller
{
    public function index(CurrentContext $context, ReportingService $reporting)
    {
        $period = in_array(request('periode'), ['month', 'prev_month', 'year', 'prev_year', 'all'], true)
            ? request('periode')
            : 'month';
        $organization = $context->organization();
        $properties = Property::query()->orderBy('name')->get();
        $propertyId = request()->integer('propriete') ?: null;
        if ($propertyId && ! $properties->contains('id', $propertyId)) {
            $propertyId = null;
        }
        $dashboard = $reporting->dashboard($organization, $period, $propertyId);
        if ($propertyId) {
            $dashboard['overdue'] = $dashboard['overdue']
                ->filter(fn ($invoice) => (int) $invoice->property_id === $propertyId)
                ->values();
            $dashboard['late_count'] = $dashboard['overdue']->pluck('tenant_id')->unique()->count();
            $totals = [];
            foreach ($dashboard['overdue'] as $invoice) {
                $totals[$invoice->currency] = ($totals[$invoice->currency] ?? 0) + $invoice->balanceMinor();
            }
            $dashboard['late_totals'] = $totals;
            $dashboard['maintenance'] = $dashboard['maintenance']
                ->filter(fn ($item) => (int) $item->unit?->property_id === $propertyId)
                ->values();
        }
        $currency = array_key_first($dashboard['currencies']) ?: $organization->preference('default_currency', 'USD');
        $dashboard['series'] = $reporting->monthlySeries($organization, (string) $currency, $propertyId);
        $dashboard['properties'] = $properties;
        $dashboard['property_id'] = $propertyId;
        $dashboard['chart_currency'] = $currency;
        $dashboard['open_maintenance'] = MaintenanceRequest::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->when($propertyId, fn ($query) => $query->whereHas('unit', fn ($unit) => $unit->where('property_id', $propertyId)))
            ->count();
        $role = $context->member()->role;
        $view = match ($role) {
            MemberRole::Collector => 'office.dashboard-collector',
            MemberRole::Technician => 'office.dashboard-technician',
            default => 'office.dashboard',
        };

        return view($view, $dashboard);
    }

    public function more()
    {
        return view('office.more');
    }

    public function reports(CurrentContext $context, ReportingService $reporting)
    {
        $period = in_array(request('periode'), ['month', 'prev_month', 'year', 'prev_year', 'all'], true)
            ? request('periode')
            : 'month';
        $propertyId = request()->integer('propriete') ?: null;
        $properties = Property::query()->orderBy('name')->get();
        if ($propertyId && ! $properties->contains('id', $propertyId)) {
            $propertyId = null;
        }
        [$start, $end, $label] = $reporting->range($context->organization(), $period);
        $financials = $reporting->financials($context->organization(), $start, $end, $propertyId);
        $currency = request('devise') ?: (array_key_first($financials) ?: $context->organization()->preference('default_currency'));
        $series = $reporting->monthlySeries($context->organization(), $currency, $propertyId);
        $breakdown = $reporting->propertyBreakdown($context->organization(), $start, $end);
        $categories = $reporting->expensesByCategory($context->organization(), $start, $end, $propertyId);
        $stock = $reporting->stock($context->organization());
        $now = CarbonImmutable::now($context->organization()->timezone);
        $year = $reporting->financials($context->organization(), $now->startOfYear(), $now->endOfYear(), $propertyId);
        $lifetime = $reporting->financials($context->organization(), null, null, $propertyId);

        return view('office.reports', compact(
            'period', 'label', 'financials', 'series', 'breakdown', 'categories', 'stock', 'currency', 'properties', 'propertyId', 'year', 'lifetime',
        ));
    }

    public function audit(CurrentContext $context)
    {
        $logs = AuditLog::query()
            ->with('user')
            ->latest('id')
            ->paginate(30);

        return view('office.audit', compact('logs'));
    }

    public function prorataOptions(ProrataManager $prorata): array
    {
        return $prorata->options();
    }
}
