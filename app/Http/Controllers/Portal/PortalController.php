<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Enums\InvoiceType;
use App\Enums\MaintenanceUrgency;
use App\Enums\PaymentMethod;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\CashCollection;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\MessageThread;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Services\Billing\BillingCalendar;
use App\Services\BillingService;
use App\Services\CashCollectionService;
use App\Services\MaintenanceService;
use App\Services\MessageService;
use App\Services\MoveOutService;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\ReportingService;
use App\Support\CurrentContext;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PortalController extends Controller
{
    public function dashboard(CurrentContext $context, BillingService $billing, BillingCalendar $calendar, ReportingService $reporting)
    {
        $tenant = $context->tenant();
        $contract = Contract::query()->with('unit.property')->where('tenant_id', $tenant->id)->whereIn('status', ['active', 'move_out_requested', 'pending'])->latest('start_date')->first();
        $invoices = Invoice::query()->withBalance()->where('tenant_id', $tenant->id)->latest('due_on')->limit(8)->get();
        $today = CarbonImmutable::now($context->organization()->timezone)->startOfDay();
        $open = $invoices->filter(fn ($invoice) => ! in_array($invoice->status->value, ['paid', 'cancelled'], true));
        $primary = $contract?->currency ?? $context->organization()->preference('default_currency');
        $due = (int) $open->where('currency', $primary)->sum(fn ($invoice) => $invoice->balanceMinor());
        $paid = (int) $invoices->where('currency', $primary)->sum(fn ($invoice) => $invoice->netPaidMinor());
        $next = $open->sortBy('due_on')->first();
        $monthKey = $today->format('Y-m');
        $charges = [];
        foreach ([InvoiceType::Rent, InvoiceType::Water, InvoiceType::Electricity] as $type) {
            $rows = $invoices->filter(fn ($invoice) => $invoice->type === $type && $invoice->period_key === $monthKey);
            if ($rows->isEmpty() && $type !== InvoiceType::Rent) {
                continue;
            }
            $charges[] = [
                'label' => $type->label(),
                'currency' => $rows->first()->currency ?? $primary,
                'paid' => (int) $rows->sum(fn ($invoice) => $invoice->netPaidMinor()),
                'due' => (int) $rows->sum(fn ($invoice) => $invoice->balanceMinor()),
                'status' => $rows->first()?->status,
            ];
        }

        $owner = OrganizationMember::withoutGlobalScopes()
            ->with('user')
            ->where('organization_id', $context->organization()->id)
            ->where('role', 'owner')
            ->first();
        $snapshot = $reporting->tenantSnapshot($context->organization(), $tenant->id, $primary);
        $payments = Payment::query()->with('invoice')->where('tenant_id', $tenant->id)->latest('id')->limit(8)->get();

        return view('portal.dashboard', [
            'tenant' => $tenant,
            'contract' => $contract,
            'owner' => $owner,
            'invoices' => $invoices,
            'charges' => $charges,
            'due' => $due,
            'paid' => $paid,
            'next' => $next,
            'daysLate' => $next && $next->status->value === 'overdue'
                ? $calendar->daysLate($today, CarbonImmutable::parse($next->due_on))
                : 0,
            'currency' => $primary,
            'snapshot' => $snapshot,
            'payments' => $payments,
        ]);
    }

    public function contract(CurrentContext $context)
    {
        $contract = Contract::query()->with('unit.property')->where('tenant_id', $context->tenant()->id)->latest('start_date')->first();

        return view('portal.contract', compact('contract'));
    }

    public function invoices(CurrentContext $context)
    {
        $invoices = Invoice::query()->withBalance()->where('tenant_id', $context->tenant()->id)->latest('due_on')->paginate(20);

        return view('portal.invoices.index', compact('invoices'));
    }

    public function showInvoice(Invoice $invoice, CurrentContext $context, BillingService $billing)
    {
        $this->authorize('view', $invoice);
        $billing->applyStatus($invoice, CarbonImmutable::now($context->organization()->timezone));
        $invoice->load('items', 'unit');
        $payments = Payment::query()->with('declarer', 'reviewer')->where('invoice_id', $invoice->id)->latest('id')->get();
        $agents = OrganizationMember::withoutGlobalScopes()
            ->with('user')
            ->where('organization_id', $context->organization()->id)
            ->where('status', 'active')
            ->get()
            ->filter(fn ($member) => $member->hasPermission(Permission::CollectionsRecord));

        return view('portal.invoices.show', [
            'invoice' => $invoice,
            'balance' => $billing->balanceOf($invoice),
            'agents' => $agents,
            'payments' => $payments,
        ]);
    }

    public function declarePayment(Request $request, Invoice $invoice, CurrentContext $context, PaymentService $payments)
    {
        $this->authorize('view', $invoice);
        $data = $request->validate([
            'amount' => ['required', 'string', 'max:20'],
            'method' => ['required', Rule::in(['transfer', 'other'])],
            'note' => ['nullable', 'string', 'max:500'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ], [
            'proof.required' => 'Joignez la preuve du paiement pour qu’elle puisse être confirmée.',
        ]);
        $payments->declare(
            $context->organization(),
            $invoice,
            $request->user(),
            Money::toMinor($data['amount']),
            PaymentMethod::from($data['method']),
            $data['note'] ?? null,
            store_upload($request->file('proof'), 'proofs'),
        );

        return back()->with('status', 'Preuve envoyée. Le paiement est à confirmer : il n’entre dans les encaissements qu’une fois approuvé.');
    }

    public function cashPayment(Request $request, Invoice $invoice, CurrentContext $context, CashCollectionService $cash)
    {
        $this->authorize('view', $invoice);
        $data = $request->validate([
            'amount' => ['required', 'string', 'max:20'],
            'agent_id' => ['required', 'integer'],
        ]);
        $agent = User::query()->findOrFail($data['agent_id']);
        $cash->initiate($context->organization(), $invoice, $request->user(), $agent, Money::toMinor($data['amount']), true);

        return back()->with('status', 'Demande d\'encaissement créée. L\'agent doit confirmer la réception.');
    }

    public function confirmCash(CashCollection $collection, Request $request, CurrentContext $context, CashCollectionService $cash)
    {
        abort_unless((int) $collection->tenant_id === (int) $context->tenant()->id, 403);
        $cash->confirm($collection, $request->user(), true);

        return back()->with('status', 'Vous avez confirmé la remise des espèces.');
    }

    public function maintenance(CurrentContext $context)
    {
        $items = MaintenanceRequest::query()->where('tenant_id', $context->tenant()->id)->latest()->get();

        return view('portal.maintenance.index', compact('items'));
    }

    public function storeMaintenance(Request $request, CurrentContext $context, MaintenanceService $maintenance)
    {
        $contract = Contract::query()->where('tenant_id', $context->tenant()->id)->whereIn('status', ['active', 'move_out_requested'])->firstOrFail();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:3000'],
            'urgency' => ['required', Rule::enum(MaintenanceUrgency::class)],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);
        $maintenance->report($context->organization(), $contract->unit, $request->user(), [
            'title' => $data['title'],
            'description' => $data['description'],
            'urgency' => MaintenanceUrgency::from($data['urgency']),
            'photo_path' => store_upload($request->file('photo'), 'maintenance'),
        ], $context->tenant()->id);

        return back()->with('status', 'Incident signalé.');
    }

    public function showMaintenance(MaintenanceRequest $maintenance)
    {
        $this->authorize('view', $maintenance);
        $maintenance->load('updates.user', 'unit', 'tenant', 'reporter', 'handler');

        return view('portal.maintenance.show', ['item' => $maintenance]);
    }

    public function commentMaintenance(Request $request, MaintenanceRequest $maintenance, MaintenanceService $service)
    {
        $this->authorize('view', $maintenance);
        $data = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);
        $service->comment($maintenance, $request->user(), $data['note'], store_upload($request->file('photo'), 'maintenance'));

        return back()->with('status', 'Commentaire ajouté.');
    }

    public function moveOutForm(CurrentContext $context)
    {
        $contract = Contract::query()->with('unit')->where('tenant_id', $context->tenant()->id)->whereIn('status', ['active', 'move_out_requested'])->first();

        return view('portal.moveout', compact('contract'));
    }

    public function storeMoveOut(Request $request, CurrentContext $context, MoveOutService $moveOuts)
    {
        $contract = Contract::query()->where('tenant_id', $context->tenant()->id)->where('status', 'active')->firstOrFail();
        $data = $request->validate([
            'planned_on' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $moveOuts->request($contract, $request->user(), CarbonImmutable::parse($data['planned_on']), $data['reason'] ?? null);

        return back()->with('status', 'Départ demandé. Le logement reste occupé jusqu\'à la validation.');
    }

    public function messages(CurrentContext $context)
    {
        $threads = MessageThread::query()
            ->whereHas('participants', fn ($query) => $query->where('users.id', $context->tenant()->user_id))
            ->with(['participants', 'messages' => fn ($query) => $query->reorder()->latest('id')->limit(1)])
            ->latest('updated_at')
            ->get();
        $owner = OrganizationMember::withoutGlobalScopes()->with('user')->where('organization_id', $context->organization()->id)->where('role', 'owner')->first();

        return view('portal.messages.index', compact('threads', 'owner'));
    }

    public function storeMessage(Request $request, CurrentContext $context, MessageService $messages)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);
        $owner = OrganizationMember::withoutGlobalScopes()->where('organization_id', $context->organization()->id)->where('role', 'owner')->firstOrFail();
        $message = $messages->send($context->organization(), $request->user(), $owner->user, $data['body'], store_upload($request->file('attachment'), 'messages'));

        return redirect()->route('portal.messages.show', $message->message_thread_id);
    }

    public function showThread(MessageThread $thread, CurrentContext $context, MessageService $messages)
    {
        $this->authorize('view', $thread);
        $thread->load('messages.sender', 'participants');
        $messages->markRead($thread, request()->user());
        $threads = MessageThread::query()
            ->whereHas('participants', fn ($query) => $query->where('users.id', $context->tenant()->user_id))
            ->with(['participants', 'messages' => fn ($query) => $query->reorder()->latest('id')->limit(1)])
            ->latest('updated_at')
            ->get();

        return view('portal.messages.show', compact('thread', 'threads'));
    }

    public function reply(Request $request, MessageThread $thread, CurrentContext $context, MessageService $messages)
    {
        $this->authorize('view', $thread);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);
        $thread->load('participants');
        $recipient = $thread->participants->first(fn ($user) => $user->id !== $request->user()->id);
        abort_if($recipient === null, 404);
        $messages->send($context->organization(), $request->user(), $recipient, $data['body'], store_upload($request->file('attachment'), 'messages'));

        return back();
    }

    public function notifications(Request $request)
    {
        return view('portal.notifications', [
            'items' => $request->user()->notifications()->latest()->paginate(30),
        ]);
    }

    public function readNotifications(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
