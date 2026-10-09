<?php

declare(strict_types=1);

namespace App\Http\Controllers\Office;

use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceUrgency;
use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MaintenanceRequest;
use App\Models\MoveOutRequest;
use App\Models\OrganizationMember;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\BillingService;
use App\Services\ExpenseService;
use App\Services\MaintenanceService;
use App\Services\MoveOutService;
use App\Support\CurrentContext;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperationController extends Controller
{
    public function expenses(CurrentContext $context)
    {
        return view('office.expenses.index', [
            'expenses' => Expense::query()->with('category', 'unit', 'property', 'tenant', 'recorder')->latest('spent_on')->paginate(20),
            'categories' => ExpenseCategory::query()->orderBy('name')->get(),
            'properties' => Property::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
            'tenants' => Tenant::query()->orderBy('name')->get(),
            'currencies' => $context->organization()->currencies(),
        ]);
    }

    public function storeExpense(Request $request, CurrentContext $context, ExpenseService $expenses)
    {
        $data = $request->validate([
            'expense_category_id' => ['required', 'integer'],
            'amount' => ['required', 'string', 'max:20'],
            'currency' => ['required', Rule::in($context->organization()->currencies())],
            'spent_on' => ['required', 'date'],
            'motif' => ['required', 'string', 'max:180'],
            'property_id' => ['nullable', 'integer'],
            'unit_id' => ['nullable', 'integer'],
            'tenant_id' => ['nullable', 'integer'],
            'payee' => ['nullable', 'string', 'max:120'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);

        $expenses->record($context->organization(), $request->user(), [
            'expense_category_id' => $data['expense_category_id'],
            'amount_minor' => Money::toMinor($data['amount']),
            'currency' => $data['currency'],
            'spent_on' => $data['spent_on'],
            'motif' => $data['motif'],
            'property_id' => $data['property_id'] ?: null,
            'unit_id' => $data['unit_id'] ?: null,
            'tenant_id' => $data['tenant_id'] ?: null,
            'payee' => $data['payee'] ?? null,
            'comment' => $data['comment'] ?? null,
            'attachment_path' => store_upload($request->file('attachment'), 'expenses'),
        ]);

        return back()->with('status', 'Dépense enregistrée.');
    }

    public function voidExpense(Request $request, Expense $expense, ExpenseService $expenses)
    {
        $this->authorize('manage', $expense);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $expenses->void($expense, $request->user(), $data['reason']);

        return back()->with('status', 'Dépense annulée. Elle reste dans l\'historique.');
    }

    public function maintenance()
    {
        $items = MaintenanceRequest::query()->with('unit', 'tenant', 'reporter')->latest()->paginate(20);

        return view('office.maintenance.index', [
            'items' => $items,
            'units' => Unit::query()->orderBy('name')->get(),
        ]);
    }

    public function storeMaintenance(Request $request, CurrentContext $context, MaintenanceService $maintenance)
    {
        $data = $request->validate([
            'unit_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:3000'],
            'urgency' => ['required', Rule::enum(MaintenanceUrgency::class)],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);
        $unit = Unit::query()->findOrFail($data['unit_id']);
        $maintenance->report($context->organization(), $unit, $request->user(), [
            'title' => $data['title'],
            'description' => $data['description'],
            'urgency' => MaintenanceUrgency::from($data['urgency']),
            'photo_path' => store_upload($request->file('photo'), 'maintenance'),
        ]);

        return back()->with('status', 'Incident ouvert.');
    }

    public function showMaintenance(MaintenanceRequest $maintenance)
    {
        $this->authorize('view', $maintenance);
        $maintenance->load('unit', 'tenant', 'updates.user', 'assignee', 'reporter', 'handler');

        return view('office.maintenance.show', [
            'item' => $maintenance,
            'next' => $maintenance->status->next(),
            'members' => OrganizationMember::query()->with('user')->where('status', 'active')->get(),
        ]);
    }

    public function advanceMaintenance(Request $request, MaintenanceRequest $maintenance, MaintenanceService $service)
    {
        $this->authorize('manage', $maintenance);
        $data = $request->validate([
            'status' => ['required', Rule::enum(MaintenanceStatus::class)],
            'note' => ['nullable', 'string', 'max:2000'],
            'estimated_cost' => ['nullable', 'string', 'max:20'],
            'actual_cost' => ['nullable', 'string', 'max:20'],
            'currency' => ['nullable', 'string', 'size:3'],
            'assigned_to' => ['nullable', 'integer'],
            'payee' => ['nullable', 'string', 'max:120'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $service->advance($maintenance, $request->user(), MaintenanceStatus::from($data['status']), [
            'note' => $data['note'] ?? null,
            'estimated_cost_minor' => $data['estimated_cost'] ? Money::toMinor($data['estimated_cost']) : null,
            'actual_cost_minor' => $data['actual_cost'] ? Money::toMinor($data['actual_cost']) : null,
            'currency' => $data['currency'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'payee' => $data['payee'] ?? null,
            'photo_path' => store_upload($request->file('photo'), 'maintenance'),
        ]);

        return back()->with('status', 'Intervention mise à jour.');
    }

    public function acceptMaintenance(Request $request, MaintenanceRequest $maintenance, CurrentContext $context, MaintenanceService $service)
    {
        $this->authorize('manage', $maintenance);
        $member = $context->member();
        abort_unless($member && in_array($member->role, [MemberRole::Owner, MemberRole::Manager], true), 403, 'Seul le bailleur ou le gérant peut accepter une urgence.');
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $service->accept($maintenance, $request->user(), $data['note'] ?? null);

        return back()->with('status', 'Urgence acceptée comme bien gérée. L\'alerte ne s\'affiche plus.');
    }

    public function showMoveOut(MoveOutRequest $moveOut, BillingService $billing)
    {
        $this->authorize('view', $moveOut);
        $moveOut->load('tenant', 'contract.unit', 'contract.invoices');
        $balance = $moveOut->contract->invoices->sum(fn ($invoice) => $invoice->status->value === 'cancelled' ? 0 : $billing->balanceOf($invoice));

        return view('office.moveouts.show', compact('moveOut', 'balance'));
    }

    public function completeMoveOut(Request $request, MoveOutRequest $moveOut, MoveOutService $moveOuts)
    {
        $this->authorize('manage', $moveOut);
        $data = $request->validate([
            'payments_checked' => ['accepted'],
            'debts_checked' => ['accepted'],
            'condition_checked' => ['accepted'],
            'equipment_checked' => ['accepted'],
            'review_note' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);
        $moveOuts->complete($moveOut, $request->user(), [
            'payments_checked' => true,
            'debts_checked' => true,
            'condition_checked' => true,
            'equipment_checked' => true,
        ], $data['review_note'] ?? null, store_upload($request->file('photo'), 'moveouts'));

        return redirect()->route('office.dashboard')->with('status', 'Sortie validée. Le logement est disponible.');
    }

    public function rejectMoveOut(Request $request, MoveOutRequest $moveOut, MoveOutService $moveOuts)
    {
        $this->authorize('manage', $moveOut);
        $data = $request->validate(['review_note' => ['required', 'string', 'max:500']]);
        $moveOuts->reject($moveOut, $request->user(), $data['review_note']);

        return back()->with('status', 'Départ refusé. Le contrat redevient actif.');
    }
}
