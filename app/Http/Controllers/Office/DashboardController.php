<?php

declare(strict_types=1);

namespace App\Http\Controllers\Office;

use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Billing\ProrataManager;
use App\Services\ReportingService;
use App\Support\CurrentContext;

class DashboardController extends Controller
{
    public function index(CurrentContext $context, ReportingService $reporting)
    {
        $period = in_array(request('periode'), ['month', 'prev_month', 'year', 'prev_year', 'all'], true)
            ? request('periode')
            : 'month';
        $dashboard = $reporting->dashboard($context->organization(), $period);
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
        [$start, $end, $label] = $reporting->range($context->organization(), $period);
        $financials = $reporting->financials($context->organization(), $start, $end);
        $currency = array_key_first($financials) ?: $context->organization()->preference('default_currency');
        $series = $reporting->monthlySeries($context->organization(), $currency);
        $properties = $reporting->propertyBreakdown($context->organization(), $start, $end);
        $categories = $reporting->expensesByCategory($context->organization(), $start, $end);
        $stock = $reporting->stock($context->organization());

        return view('office.reports', compact('period', 'label', 'financials', 'series', 'properties', 'categories', 'stock', 'currency'));
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
