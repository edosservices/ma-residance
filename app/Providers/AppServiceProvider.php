<?php

namespace App\Providers;

use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceUrgency;
use App\Enums\MemberRole;
use App\Models\CashCollection;
use App\Models\CashRemittance;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\MessageThread;
use App\Models\MoveOutRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Policies\ResidencePolicy;
use App\Services\Billing\DailyProrata;
use App\Services\Billing\ProrataManager;
use App\Services\Payments\DeclaredPaymentChannel;
use App\Services\Payments\PaymentChannelRegistry;
use App\Support\CurrentContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrentContext::class);
        $this->app->singleton(ProrataManager::class, fn () => new ProrataManager([
            'daily' => new DailyProrata,
        ]));
        $this->app->singleton(PaymentChannelRegistry::class, fn () => new PaymentChannelRegistry([
            'manual' => new DeclaredPaymentChannel,
        ]));
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        foreach ([
            Property::class, Unit::class, Tenant::class, Contract::class, Invoice::class,
            Payment::class, CashCollection::class, CashRemittance::class, Expense::class,
            MaintenanceRequest::class, MoveOutRequest::class, MessageThread::class,
        ] as $model) {
            Gate::policy($model, ResidencePolicy::class);
        }

        View::composer(['layouts.shell', 'layouts.guest'], function ($view) {
            $user = auth()->user();
            $context = app(CurrentContext::class);
            $member = $context->member();
            $urgentMaintenance = collect();

            if ($member && in_array($member->role, [MemberRole::Owner, MemberRole::Manager], true)) {
                $urgentMaintenance = MaintenanceRequest::query()
                    ->with(['unit', 'tenant', 'reporter'])
                    ->where('urgency', MaintenanceUrgency::High->value)
                    ->whereNull('handled_at')
                    ->whereNotIn('status', [MaintenanceStatus::Done->value, MaintenanceStatus::Cancelled->value])
                    ->latest()
                    ->get();

                if (request()->routeIs('office.maintenance.show')) {
                    $current = request()->route('maintenance');
                    $currentId = $current instanceof MaintenanceRequest ? $current->id : $current;
                    $urgentMaintenance = $urgentMaintenance->reject(fn (MaintenanceRequest $item) => (int) $item->id === (int) $currentId);
                }
            }

            $view->with([
                'currentUser' => $user,
                'currentOrganization' => $context->organizationId() ? $context->organization() : null,
                'currentMember' => $member,
                'urgentMaintenance' => $urgentMaintenance,
                'unreadNotifications' => $user ? $user->unreadNotifications()->count() : 0,
                'recentNotifications' => $user ? $user->notifications()->latest()->limit(8)->get() : collect(),
            ]);
        });
    }
}
