<?php

declare(strict_types=1);

namespace App\Http\Controllers\Office;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\CashCollection;
use App\Models\CashRemittance;
use App\Models\Invoice;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Services\CashCollectionService;
use App\Services\RemittanceService;
use App\Support\CurrentContext;
use App\Support\Money;
use Illuminate\Http\Request;

class CashController extends Controller
{
    public function collections(CurrentContext $context)
    {
        $collections = CashCollection::query()->with('tenant', 'agent', 'invoice')->latest()->paginate(20);
        $invoices = Invoice::query()->with('tenant')->whereNotIn('status', ['paid', 'cancelled'])->latest()->limit(80)->get();
        $agents = OrganizationMember::query()->with('user')->where('status', 'active')->get()
            ->filter(fn ($member) => $member->hasPermission(Permission::CollectionsRecord));

        return view('office.collections.index', compact('collections', 'invoices', 'agents'));
    }

    public function storeCollection(Request $request, CurrentContext $context, CashCollectionService $cash)
    {
        $data = $request->validate([
            'invoice_id' => ['required', 'integer'],
            'agent_id' => ['required', 'integer'],
            'amount' => ['required', 'string', 'max:20'],
        ]);
        $invoice = Invoice::query()->findOrFail($data['invoice_id']);
        $this->authorize('view', $invoice);
        $agent = User::query()->findOrFail($data['agent_id']);
        $cash->initiate($context->organization(), $invoice, $request->user(), $agent, Money::toMinor($data['amount']), false);

        return back()->with('status', 'Encaissement ouvert. Les deux confirmations valident le paiement.');
    }

    public function showCollection(CashCollection $collection)
    {
        $this->authorize('view', $collection);
        $collection->load('tenant.user', 'agent', 'invoice', 'payment');

        return view('office.collections.show', compact('collection'));
    }

    public function confirmCollection(CashCollection $collection, Request $request, CashCollectionService $cash)
    {
        $this->authorize('manage', $collection);
        $cash->confirm($collection, $request->user(), false);

        return back()->with('status', 'Confirmation enregistrée.');
    }

    public function cancelCollection(CashCollection $collection, Request $request, CashCollectionService $cash)
    {
        $this->authorize('manage', $collection);
        $cash->cancel($collection, $request->user());

        return back()->with('status', 'Encaissement annulé.');
    }

    public function remittances()
    {
        $remittances = CashRemittance::query()->with('agent')->latest()->paginate(20);

        return view('office.remittances.index', compact('remittances'));
    }

    public function storeRemittance(Request $request, CurrentContext $context, RemittanceService $remittances)
    {
        $data = $request->validate(['currency' => ['required', 'string', 'size:3']]);
        $remittances->submit($context->organization(), $request->user(), strtoupper($data['currency']));

        return back()->with('status', 'Remise déclarée. Le bailleur doit confirmer la réception.');
    }

    public function showRemittance(CashRemittance $remittance)
    {
        $this->authorize('view', $remittance);
        $remittance->load('agent', 'items.collection.tenant');

        return view('office.remittances.show', compact('remittance'));
    }

    public function confirmRemittance(CashRemittance $remittance, Request $request, RemittanceService $remittances)
    {
        $this->authorize('manage', $remittance);
        $remittances->confirm($remittance, $request->user());

        return back()->with('status', 'Réception des fonds confirmée.');
    }

    public function rejectRemittance(Request $request, CashRemittance $remittance, RemittanceService $remittances)
    {
        $this->authorize('manage', $remittance);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $remittances->reject($remittance, $request->user(), $data['reason']);

        return back()->with('status', 'Remise refusée. Les espèces restent chez l\'agent.');
    }
}
