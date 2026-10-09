<?php

declare(strict_types=1);

namespace App\Http\Controllers\Office;

use App\Enums\AllocationMethod;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\OrganizationMember;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenant;
use App\Services\BillingService;
use App\Services\PaymentService;
use App\Services\UtilityService;
use App\Support\CurrentContext;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceController extends Controller
{
    public function invoices(Request $request)
    {
        $invoices = Invoice::query()
            ->with('tenant', 'unit')
            ->withBalance()
            ->when($request->string('statut')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString();

        return view('office.invoices.index', compact('invoices'));
    }

    public function showInvoice(Invoice $invoice, BillingService $billing, CurrentContext $context)
    {
        $this->authorize('view', $invoice);
        $billing->applyStatus($invoice, CarbonImmutable::now($context->organization()->timezone));
        $invoice->load('tenant', 'unit', 'items', 'contract');
        $agents = OrganizationMember::query()->with('user')->where('status', 'active')->get()
            ->filter(fn ($member) => $member->hasPermission(Permission::CollectionsRecord));

        return view('office.invoices.show', [
            'invoice' => $invoice,
            'balance' => $billing->balanceOf($invoice),
            'agents' => $agents,
        ]);
    }

    public function cancelInvoice(Request $request, Invoice $invoice, BillingService $billing)
    {
        $this->authorize('manage', $invoice);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $billing->cancel($invoice, $request->user(), $data['reason']);

        return back()->with('status', 'Facture annulée.');
    }

    public function createUtility(CurrentContext $context)
    {
        return view('office.utilities.create', [
            'properties' => Property::query()->where('status', 'active')->orderBy('name')->get(),
            'currencies' => $context->organization()->currencies(),
        ]);
    }

    public function storeUtility(Request $request, CurrentContext $context, UtilityService $utilities)
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer'],
            'type' => ['required', Rule::in(['water', 'electricity'])],
            'month' => ['required', 'date_format:Y-m'],
            'total' => ['required', 'string', 'max:20'],
            'currency' => ['required', Rule::in($context->organization()->currencies())],
            'method' => ['required', Rule::enum(AllocationMethod::class)],
        ]);
        $property = Property::query()->findOrFail($data['property_id']);
        $utilities->allocate(
            $context->organization(),
            $property,
            $request->user(),
            InvoiceType::from($data['type']),
            CarbonImmutable::parse($data['month'].'-01'),
            Money::toMinor($data['total']),
            $data['currency'],
            AllocationMethod::from($data['method']),
        );

        return redirect()->route('office.invoices.index')->with('status', 'Charge répartie et factures créées.');
    }

    public function payments(Request $request)
    {
        $payments = Payment::query()
            ->with('tenant', 'invoice', 'declarer')
            ->when($request->query('statut') === 'pending', fn ($query) => $query->where('status', 'pending')->where('kind', 'payment'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('office.payments.index', compact('payments'));
    }

    public function createPayment()
    {
        $invoices = Invoice::query()
            ->with(['tenant', 'unit'])
            ->withBalance()
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->orderBy('due_on')
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->balanceMinor() > 0)
            ->values();

        return view('office.payments.create', [
            'tenants' => Tenant::query()->orderBy('name')->get(),
            'invoices' => $invoices,
        ]);
    }

    public function storePayment(Request $request, CurrentContext $context, PaymentService $payments)
    {
        $data = $request->validate([
            'invoice_id' => ['required', 'integer'],
            'amount' => ['required', 'string', 'max:20'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'note' => ['nullable', 'string', 'max:500'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ], [
            'invoice_id.required' => 'Choisissez le client et la facture à régler.',
            'amount.required' => 'Indiquez le montant, par exemple 150.00.',
        ]);
        $invoice = Invoice::query()->findOrFail($data['invoice_id']);
        $this->authorize('view', $invoice);
        $payments->declare(
            $context->organization(),
            $invoice,
            $request->user(),
            Money::toMinor($data['amount']),
            PaymentMethod::from($data['method']),
            $data['note'] ?? null,
            store_upload($request->file('proof'), 'proofs'),
        );

        return redirect()->route('office.payments.index')->with('status', 'Paiement déclaré.');
    }

    public function showPayment(Payment $payment)
    {
        $this->authorize('view', $payment);
        $payment->load('tenant', 'invoice', 'declarer', 'reviewer', 'reversal');

        return view('office.payments.show', compact('payment'));
    }

    public function approvePayment(Payment $payment, PaymentService $payments, Request $request)
    {
        $this->authorize('manage', $payment);
        $payments->approve($payment, $request->user());

        return back()->with('status', 'Paiement approuvé. Il est compté dans les encaissements à la date de validation.');
    }

    public function rejectPayment(Request $request, Payment $payment, PaymentService $payments)
    {
        $this->authorize('manage', $payment);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $payments->reject($payment, $request->user(), $data['reason']);

        return back()->with('status', 'Paiement rejeté.');
    }

    public function reversePayment(Request $request, Payment $payment, PaymentService $payments)
    {
        $this->authorize('manage', $payment);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $payments->reverse($payment, $request->user(), $data['reason']);

        return back()->with('status', 'Écriture de correction enregistrée. Le paiement d\'origine est conservé.');
    }
}
