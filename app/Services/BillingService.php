<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrganizationStatus;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReminder;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use App\Services\Billing\BillingCalendar;
use App\Services\Billing\ProrataManager;
use App\Support\DomainException;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function __construct(
        private ProrataManager $prorata,
        private BillingCalendar $calendar,
        private CounterService $counters,
        private AuditLogger $audit,
        private NotificationDispatcher $notifications,
    ) {}

    public function calendar(): BillingCalendar
    {
        return $this->calendar;
    }

    public function generateDue(?CarbonImmutable $today = null): void
    {
        Organization::query()
            ->where('status', OrganizationStatus::Active)
            ->orderBy('id')
            ->each(function (Organization $organization) use ($today) {
                $day = ($today ?? CarbonImmutable::now($organization->timezone))->timezone($organization->timezone)->startOfDay();
                $this->activateDueContracts($organization, $day);
                $this->generateForOrganization($organization, $day);
                $this->refreshOpenInvoices($organization, $day);
            });
    }

    public function activateDueContracts(Organization $organization, CarbonImmutable $today): void
    {
        $contracts = Contract::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('status', ContractStatus::Pending)
            ->whereDate('start_date', '<=', $today->toDateString())
            ->get();

        foreach ($contracts as $contract) {
            DB::transaction(function () use ($contract, $today) {
                $locked = Contract::withoutGlobalScopes()->lockForUpdate()->find($contract->id);

                if ($locked === null || $locked->status !== ContractStatus::Pending) {
                    return;
                }

                $locked->status = ContractStatus::Active;
                $locked->activated_at = $today;
                $locked->save();
                Unit::withoutGlobalScopes()->whereKey($locked->unit_id)->update(['status' => 'occupied']);
                $this->ensureInvoices($locked, $today);
            });
        }
    }

    public function generateForOrganization(Organization $organization, CarbonImmutable $today): void
    {
        Contract::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->whereIn('status', [ContractStatus::Active, ContractStatus::MoveOutRequested])
            ->orderBy('id')
            ->each(function (Contract $contract) use ($today) {
                $this->ensureInvoices($contract, $today);
            });
    }

    public function ensureInvoices(Contract $contract, CarbonImmutable $today): void
    {
        $start = CarbonImmutable::parse($contract->start_date)->startOfMonth();
        $cursor = $start;
        $limit = $today->startOfMonth();

        if ($contract->end_date) {
            $endMonth = CarbonImmutable::parse($contract->end_date)->startOfMonth();
            if ($endMonth->lessThan($limit)) {
                $limit = $endMonth;
            }
        }

        $started = CarbonImmutable::parse($contract->start_date);

        while ($cursor->lessThanOrEqualTo($limit)) {
            $generationDay = min((int) $contract->generation_day, $cursor->daysInMonth);
            $generationDate = $cursor->day($generationDay);
            $isCurrent = $cursor->isSameMonth($today);
            $isStartMonth = $cursor->isSameMonth($started);
            $shouldIssue = $isStartMonth || ! $isCurrent || $today->greaterThanOrEqualTo($generationDate);

            if ($shouldIssue) {
                $this->issueRentForMonth($contract, $cursor);
            }

            $cursor = $cursor->addMonth();
        }
    }

    public function issueRentForMonth(Contract $contract, CarbonImmutable $month): ?Invoice
    {
        return DB::transaction(function () use ($contract, $month) {
            $contract = Contract::withoutGlobalScopes()->lockForUpdate()->findOrFail($contract->id);
            $periodKey = $month->format('Y-m');
            $dedupe = 'rent:'.$periodKey;

            $existing = Invoice::withoutGlobalScopes()
                ->where('contract_id', $contract->id)
                ->where('dedupe_key', $dedupe)
                ->first();

            if ($existing) {
                return $existing;
            }

            $periodStart = $month->startOfMonth();
            $periodEnd = $month->endOfMonth();
            $occupiedStart = CarbonImmutable::parse($contract->start_date)->startOfDay()->max($periodStart);
            $occupiedEnd = $contract->end_date
                ? CarbonImmutable::parse($contract->end_date)->startOfDay()->min($periodEnd)
                : $periodEnd;

            $amount = $this->prorata->calculate(
                $contract->prorata_method,
                (int) $contract->rent_minor,
                $occupiedStart,
                $occupiedEnd,
                $periodStart,
                $periodEnd,
            );

            if ($amount <= 0) {
                return null;
            }

            $due = $this->calendar->dueOn($month, (int) $contract->due_day);
            if ($due->lessThan($occupiedStart)) {
                $due = $occupiedStart;
            }

            try {
                $invoice = Invoice::withoutGlobalScopes()->create([
                    'organization_id' => $contract->organization_id,
                    'contract_id' => $contract->id,
                    'tenant_id' => $contract->tenant_id,
                    'unit_id' => $contract->unit_id,
                    'property_id' => $contract->property_id,
                    'number' => $this->counters->reference($contract->organization_id, 'invoice', 'FAC'),
                    'type' => InvoiceType::Rent,
                    'period_key' => $periodKey,
                    'dedupe_key' => $dedupe,
                    'period_start' => $occupiedStart->toDateString(),
                    'period_end' => $occupiedEnd->toDateString(),
                    'amount_minor' => $amount,
                    'currency' => $contract->currency,
                    'fx_rate' => $contract->fx_rate,
                    'due_on' => $due->toDateString(),
                    'status' => InvoiceStatus::Open,
                    'issued_at' => now(),
                    'notes' => $amount === (int) $contract->rent_minor ? null : 'Prorata d\'occupation',
                ]);
            } catch (UniqueConstraintViolationException) {
                return Invoice::withoutGlobalScopes()
                    ->where('contract_id', $contract->id)
                    ->where('dedupe_key', $dedupe)
                    ->first();
            }

            InvoiceItem::withoutGlobalScopes()->create([
                'organization_id' => $contract->organization_id,
                'invoice_id' => $invoice->id,
                'label' => 'Loyer '.$periodKey,
                'quantity' => 1,
                'unit_price_minor' => $amount,
                'amount_minor' => $amount,
            ]);

            $this->audit->log(
                $contract->organization_id,
                null,
                'invoice.issued',
                $invoice,
                'Facture de loyer '.$invoice->number.' émise.',
            );

            $contract->loadMissing('tenant.user');
            if ($contract->tenant?->user) {
                $this->notifications->notify(
                    $contract->tenant->user,
                    'invoice.rent',
                    'Nouveau loyer',
                    'Votre loyer de '.money($amount, $contract->currency).' est disponible.',
                    route('portal.invoices.show', $invoice),
                );
            }

            return $invoice;
        });
    }

    public function issueCharge(
        Contract $contract,
        InvoiceType $type,
        string $periodKey,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        int $amountMinor,
        string $currency,
        CarbonImmutable $dueOn,
        string $label,
        ?int $utilityChargeId = null,
        ?array $meta = null,
        ?string $dedupeKey = null,
    ): Invoice {
        if ($amountMinor <= 0) {
            throw new DomainException('Le montant de la facture doit être positif.');
        }

        $invoice = Invoice::withoutGlobalScopes()->create([
            'organization_id' => $contract->organization_id,
            'contract_id' => $contract->id,
            'tenant_id' => $contract->tenant_id,
            'unit_id' => $contract->unit_id,
            'property_id' => $contract->property_id,
            'utility_charge_id' => $utilityChargeId,
            'number' => $this->counters->reference($contract->organization_id, 'invoice', 'FAC'),
            'type' => $type,
            'period_key' => $periodKey,
            'dedupe_key' => $dedupeKey,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'due_on' => $dueOn->toDateString(),
            'status' => InvoiceStatus::Open,
            'issued_at' => now(),
        ]);

        InvoiceItem::withoutGlobalScopes()->create([
            'organization_id' => $contract->organization_id,
            'invoice_id' => $invoice->id,
            'label' => $label,
            'quantity' => 1,
            'unit_price_minor' => $amountMinor,
            'amount_minor' => $amountMinor,
            'meta' => $meta,
        ]);

        return $invoice;
    }

    public function refreshOpenInvoices(Organization $organization, ?CarbonImmutable $today = null): void
    {
        $today ??= CarbonImmutable::now($organization->timezone)->startOfDay();

        Invoice::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->whereNotIn('status', [InvoiceStatus::Paid->value, InvoiceStatus::Cancelled->value])
            ->with('contract')
            ->orderBy('id')
            ->each(function (Invoice $invoice) use ($today, $organization) {
                $this->applyStatus($invoice, $today, (int) ($invoice->contract->grace_until_day ?? $organization->preference('grace_until_day')));
            });
    }

    public function applyStatus(Invoice $invoice, CarbonImmutable $today, ?int $graceUntilDay = null): Invoice
    {
        if ($invoice->status === InvoiceStatus::Cancelled) {
            return $invoice;
        }

        $paid = $this->netPaid($invoice->id);
        $balance = (int) $invoice->amount_minor - $paid;
        $grace = $graceUntilDay ?? (int) ($invoice->contract()->withoutGlobalScopes()->value('grace_until_day') ?? 5);
        $due = CarbonImmutable::parse($invoice->due_on)->startOfDay();
        $late = $balance > 0 && $this->calendar->isLate($today, $due, $grace);

        $status = match (true) {
            $balance <= 0 => InvoiceStatus::Paid,
            $late => InvoiceStatus::Overdue,
            $paid > 0 => InvoiceStatus::Partial,
            default => InvoiceStatus::Open,
        };

        if ($invoice->status !== $status) {
            $invoice->status = $status;
            $invoice->save();
        }

        return $invoice;
    }

    public function netPaid(int $invoiceId): int
    {
        $paid = (int) DB::table('payment_allocations')
            ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->where('payment_allocations.invoice_id', $invoiceId)
            ->where('payments.status', 'approved')
            ->where('payments.kind', 'payment')
            ->sum('payment_allocations.amount_minor');

        $reversed = (int) DB::table('payment_allocations')
            ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->where('payment_allocations.invoice_id', $invoiceId)
            ->where('payments.status', 'approved')
            ->where('payments.kind', 'reversal')
            ->sum('payment_allocations.amount_minor');

        return $paid - $reversed;
    }

    public function balanceOf(Invoice $invoice): int
    {
        return max(0, (int) $invoice->amount_minor - $this->netPaid($invoice->id));
    }

    public function cancel(Invoice $invoice, User $actor, string $reason): Invoice
    {
        if ($this->netPaid($invoice->id) > 0) {
            throw new DomainException('Une facture déjà payée ne peut pas être annulée. Passez par une annulation de paiement.');
        }

        if ($invoice->status === InvoiceStatus::Cancelled) {
            return $invoice;
        }

        $invoice->status = InvoiceStatus::Cancelled;
        $invoice->cancelled_at = now();
        $invoice->cancel_reason = $reason;
        $invoice->save();

        $this->audit->log($invoice->organization_id, $actor, 'invoice.cancelled', $invoice, "A annulé la facture {$invoice->number}. {$reason}");

        return $invoice;
    }

    public function sendReminders(?CarbonImmutable $today = null): void
    {
        Organization::query()->where('status', OrganizationStatus::Active)->orderBy('id')->each(function (Organization $organization) use ($today) {
            $day = ($today ?? CarbonImmutable::now($organization->timezone))->timezone($organization->timezone)->startOfDay();
            $this->refreshOpenInvoices($organization, $day);
            $before = (int) $organization->preference('reminder_days_before', 3);
            $repeat = max(1, (int) $organization->preference('reminder_repeat_days', 3));

            Invoice::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('type', InvoiceType::Rent)
                ->whereNotIn('status', [InvoiceStatus::Paid->value, InvoiceStatus::Cancelled->value])
                ->with(['tenant.user', 'contract'])
                ->orderBy('id')
                ->each(function (Invoice $invoice) use ($day, $before, $repeat, $organization) {
                    $user = $invoice->tenant?->user;
                    if ($user === null) {
                        return;
                    }

                    $due = CarbonImmutable::parse($invoice->due_on)->startOfDay();
                    $graceDay = (int) ($invoice->contract->grace_until_day ?? $organization->preference('grace_until_day'));
                    $graceEnd = $this->calendar->graceEndsOn($due, $graceDay);
                    $daysLate = $this->calendar->daysLate($day, $due);
                    $amount = money($this->balanceOf($invoice), $invoice->currency);

                    if ($day->equalTo($due->subDays($before))) {
                        $this->remindOnce($invoice, $user, 'before', $day, 'Échéance proche', "Votre loyer de {$amount} arrive bientôt à échéance.");
                    }

                    if ($day->equalTo($due)) {
                        $this->remindOnce($invoice, $user, 'due', $day, 'Loyer à échéance', "Votre loyer de {$amount} est arrivé à échéance.");
                    }

                    if ($day->greaterThan($due) && $day->lessThanOrEqualTo($graceEnd)
                        && ! InvoiceReminder::query()->where('invoice_id', $invoice->id)->where('kind', 'after_due')->exists()) {
                        $this->remindOnce($invoice, $user, 'after_due', $day, 'Loyer non payé', "Votre loyer de {$amount} n'a pas encore été payé.");
                    }

                    if ($day->greaterThan($graceEnd)) {
                        $last = InvoiceReminder::query()
                            ->where('invoice_id', $invoice->id)
                            ->where('kind', 'overdue')
                            ->orderByDesc('sent_on')
                            ->first();

                        if ($last && CarbonImmutable::parse($last->sent_on)->diffInDays($day, true) < $repeat) {
                            return;
                        }

                        $this->remindOnce(
                            $invoice,
                            $user,
                            'overdue',
                            $day,
                            'Paiement en retard',
                            "Votre paiement est en retard de {$daysLate} jour".($daysLate > 1 ? 's' : '').'.',
                        );
                    }
                });
        });
    }

    private function remindOnce(Invoice $invoice, User $user, string $kind, CarbonImmutable $day, string $title, string $body): void
    {
        $query = InvoiceReminder::query()
            ->where('invoice_id', $invoice->id)
            ->where('kind', $kind);

        if ($kind === 'overdue') {
            $query->whereDate('sent_on', $day->toDateString());
        }

        $exists = $query->exists();

        if ($exists) {
            return;
        }

        InvoiceReminder::query()->create([
            'invoice_id' => $invoice->id,
            'kind' => $kind,
            'sent_on' => $day->toDateString(),
        ]);

        $this->notifications->notify($user, 'rent.'.$kind, $title, $body, route('portal.invoices.show', $invoice));
    }
}
