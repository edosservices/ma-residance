<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use App\Support\DomainException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private BillingService $billing,
        private CounterService $counters,
        private AuditLogger $audit,
        private NotificationDispatcher $notifications,
    ) {}

    public function declare(
        Organization $organization,
        Invoice $invoice,
        User $actor,
        int $amountMinor,
        PaymentMethod $method,
        ?string $note = null,
        ?string $proofPath = null,
    ): Payment {
        return DB::transaction(function () use ($organization, $invoice, $actor, $amountMinor, $method, $note, $proofPath) {
            $invoice = $this->lockInvoice($organization, $invoice);

            if ($invoice->status === InvoiceStatus::Cancelled) {
                throw new DomainException('Cette facture est annulée.');
            }

            $today = CarbonImmutable::now($organization->timezone);
            $this->billing->applyStatus($invoice, $today);
            $balance = $this->billing->balanceOf($invoice);

            if ($amountMinor <= 0 || $amountMinor > $balance) {
                throw new DomainException('Le montant dépasse le solde de la facture.');
            }

            $payment = Payment::withoutGlobalScopes()->create([
                'organization_id' => $organization->id,
                'tenant_id' => $invoice->tenant_id,
                'contract_id' => $invoice->contract_id,
                'invoice_id' => $invoice->id,
                'reference' => $this->counters->reference($organization->id, 'payment', 'PAY'),
                'provider' => 'manual',
                'kind' => PaymentKind::Payment,
                'amount_minor' => $amountMinor,
                'currency' => $invoice->currency,
                'method' => $method,
                'status' => PaymentStatus::Pending,
                'proof_path' => $proofPath,
                'note' => $note,
                'declared_by' => $actor->id,
            ]);

            $this->audit->log(
                $organization->id,
                $actor,
                'payment.declared',
                $payment,
                'A déclaré un paiement de '.money($amountMinor, $invoice->currency).'.',
            );

            $this->notifications->notifyMembers(
                $organization,
                Permission::PaymentsValidate,
                'payment.declared',
                'Paiement à valider',
                money($amountMinor, $invoice->currency).' déclaré pour la facture '.$invoice->number.'.',
                route('office.payments.show', $payment),
            );

            return $payment;
        });
    }

    public function approve(Payment $payment, User $actor): Payment
    {
        return DB::transaction(function () use ($payment, $actor) {
            $payment = Payment::withoutGlobalScopes()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== PaymentStatus::Pending || $payment->kind !== PaymentKind::Payment) {
                throw new DomainException('Ce paiement ne peut pas être validé.');
            }

            if ($payment->invoice_id === null) {
                throw new DomainException('Ce paiement n\'est lié à aucune facture.');
            }

            $invoice = Invoice::withoutGlobalScopes()->lockForUpdate()->findOrFail($payment->invoice_id);
            $balance = $this->billing->balanceOf($invoice);

            if ((int) $payment->amount_minor > $balance) {
                throw new DomainException('Le solde de la facture a changé. Le paiement ne peut pas être validé tel quel.');
            }

            $this->allocate($payment, $invoice, (int) $payment->amount_minor);
            $payment->status = PaymentStatus::Approved;
            $payment->reviewed_by = $actor->id;
            $payment->reviewed_at = now();
            $payment->save();

            $organization = Organization::query()->findOrFail($payment->organization_id);
            $this->billing->applyStatus($invoice, CarbonImmutable::now($organization->timezone));
            $invoice->load('tenant');

            $this->audit->log(
                $payment->organization_id,
                $actor,
                'payment.approved',
                $payment,
                'A validé le paiement '.$payment->reference.' de '.money((int) $payment->amount_minor, $payment->currency).'.',
            );

            if ($invoice->tenant?->user) {
                $this->notifications->notify(
                    $invoice->tenant->user,
                    'payment.approved',
                    'Paiement validé',
                    'Votre paiement de '.money((int) $payment->amount_minor, $payment->currency).' est validé.',
                    route('portal.invoices.show', $invoice),
                );
            }

            return $payment;
        });
    }

    public function reject(Payment $payment, User $actor, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $actor, $reason) {
            $payment = Payment::withoutGlobalScopes()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== PaymentStatus::Pending) {
                throw new DomainException('Ce paiement ne peut pas être rejeté.');
            }

            $payment->status = PaymentStatus::Rejected;
            $payment->rejection_reason = $reason;
            $payment->reviewed_by = $actor->id;
            $payment->reviewed_at = now();
            $payment->save();

            $payment->load('invoice.tenant.user');
            $this->audit->log($payment->organization_id, $actor, 'payment.rejected', $payment, 'A rejeté le paiement '.$payment->reference.'. '.$reason);

            if ($payment->invoice?->tenant?->user) {
                $this->notifications->notify(
                    $payment->invoice->tenant->user,
                    'payment.rejected',
                    'Paiement rejeté',
                    'Votre paiement '.$payment->reference.' a été rejeté. '.$reason,
                );
            }

            return $payment;
        });
    }

    public function reverse(Payment $payment, User $actor, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $actor, $reason) {
            $payment = Payment::withoutGlobalScopes()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== PaymentStatus::Approved || $payment->kind !== PaymentKind::Payment) {
                throw new DomainException('Seuls les paiements validés peuvent être annulés par une écriture de correction.');
            }

            if (Payment::withoutGlobalScopes()->where('reverses_payment_id', $payment->id)->exists()) {
                throw new DomainException('Ce paiement a déjà été corrigé.');
            }

            $originalAmount = (int) $payment->amount_minor;
            $reversal = Payment::withoutGlobalScopes()->create([
                'organization_id' => $payment->organization_id,
                'tenant_id' => $payment->tenant_id,
                'contract_id' => $payment->contract_id,
                'invoice_id' => $payment->invoice_id,
                'reference' => $this->counters->reference($payment->organization_id, 'payment', 'PAY'),
                'provider' => 'manual',
                'kind' => PaymentKind::Reversal,
                'reverses_payment_id' => $payment->id,
                'amount_minor' => $originalAmount,
                'currency' => $payment->currency,
                'method' => $payment->method,
                'status' => PaymentStatus::Approved,
                'note' => $reason,
                'declared_by' => $actor->id,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            foreach ($payment->allocations as $allocation) {
                $this->allocate($reversal, Invoice::withoutGlobalScopes()->findOrFail($allocation->invoice_id), (int) $allocation->amount_minor);
            }

            if ($payment->invoice_id) {
                $invoice = Invoice::withoutGlobalScopes()->findOrFail($payment->invoice_id);
                $organization = Organization::query()->findOrFail($payment->organization_id);
                $this->billing->applyStatus($invoice, CarbonImmutable::now($organization->timezone));
            }

            $payment->refresh();

            if ((int) $payment->amount_minor !== $originalAmount) {
                throw new DomainException('Le paiement d\'origine a été modifié, opération annulée.');
            }

            $this->audit->log(
                $payment->organization_id,
                $actor,
                'payment.reversed',
                $reversal,
                'A enregistré une annulation de '.money($originalAmount, $payment->currency).' sur '.$payment->reference.'. '.$reason,
            );

            return $reversal;
        });
    }

    public function capture(
        Organization $organization,
        Invoice $invoice,
        User $actor,
        int $amountMinor,
        PaymentMethod $method,
        ?string $note = null,
    ): Payment {
        return DB::transaction(function () use ($organization, $invoice, $actor, $amountMinor, $method, $note) {
            $invoice = $this->lockInvoice($organization, $invoice);
            $balance = $this->billing->balanceOf($invoice);

            if ($amountMinor <= 0 || $amountMinor > $balance) {
                throw new DomainException('Le montant dépasse le solde de la facture.');
            }

            $payment = Payment::withoutGlobalScopes()->create([
                'organization_id' => $organization->id,
                'tenant_id' => $invoice->tenant_id,
                'contract_id' => $invoice->contract_id,
                'invoice_id' => $invoice->id,
                'reference' => $this->counters->reference($organization->id, 'payment', 'PAY'),
                'provider' => 'manual',
                'kind' => PaymentKind::Payment,
                'amount_minor' => $amountMinor,
                'currency' => $invoice->currency,
                'method' => $method,
                'status' => PaymentStatus::Approved,
                'note' => $note,
                'declared_by' => $actor->id,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            $this->allocate($payment, $invoice, $amountMinor);
            $this->billing->applyStatus($invoice, CarbonImmutable::now($organization->timezone));

            return $payment;
        });
    }

    public function recordApproved(
        Organization $organization,
        Invoice $invoice,
        User $actor,
        int $amountMinor,
        PaymentMethod $method,
        ?string $note = null,
    ): Payment {
        $payment = $this->declare($organization, $invoice, $actor, $amountMinor, $method, $note);

        return $this->approve($payment, $actor);
    }

    private function lockInvoice(Organization $organization, Invoice $invoice): Invoice
    {
        $invoice = Invoice::withoutGlobalScopes()->lockForUpdate()->findOrFail($invoice->id);

        if ($invoice->organization_id !== $organization->id) {
            throw new DomainException('Facture introuvable.');
        }

        return $invoice;
    }

    private function allocate(Payment $payment, Invoice $invoice, int $amount): void
    {
        PaymentAllocation::withoutGlobalScopes()->create([
            'organization_id' => $payment->organization_id,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount_minor' => $amount,
        ]);
    }
}
