<?php

declare(strict_types=1);

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\RentalRequest;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\Billing\ProrataManager;
use App\Services\ContractService;
use App\Support\CurrentContext;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    public function requests()
    {
        $requests = RentalRequest::query()->with('unit.property', 'user')->latest()->paginate(20);

        return view('office.requests.index', compact('requests'));
    }

    public function showRequest(RentalRequest $rentalRequest, CurrentContext $context)
    {
        $rentalRequest->load('unit.property', 'user');

        return view('office.requests.show', [
            'rentalRequest' => $rentalRequest,
            'currencies' => $context->organization()->currencies(),
        ]);
    }

    public function accept(Request $request, RentalRequest $rentalRequest, CurrentContext $context, ContractService $contracts)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'rent' => ['required', 'string', 'max:20'],
            'currency' => ['required', Rule::in($context->organization()->currencies())],
            'conditions' => ['nullable', 'string', 'max:5000'],
        ]);

        $contracts->accept($rentalRequest, $request->user(), [
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'rent_minor' => Money::toMinor($data['rent']),
            'currency' => $data['currency'],
            'conditions' => $data['conditions'] ?? null,
        ]);

        return redirect()->route('office.contracts.index')->with('status', 'Demande acceptée. Le contrat est créé.');
    }

    public function refuse(Request $request, RentalRequest $rentalRequest, ContractService $contracts)
    {
        $data = $request->validate(['decision_note' => ['nullable', 'string', 'max:500']]);
        $contracts->refuse($rentalRequest, $request->user(), $data['decision_note'] ?? null);

        return back()->with('status', 'Demande refusée.');
    }

    public function index()
    {
        $contracts = Contract::query()->with('tenant', 'unit')->latest()->paginate(20);

        return view('office.contracts.index', [
            'contracts' => $contracts,
            'tenants' => Tenant::query()->orderBy('name')->get(),
            'units' => Unit::query()->where('status', 'available')->orderBy('name')->get(),
            'currencies' => app(CurrentContext::class)->organization()->currencies(),
        ]);
    }

    public function store(Request $request, CurrentContext $context, ContractService $contracts)
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer'],
            'unit_id' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'rent' => ['required', 'string', 'max:20'],
            'currency' => ['required', Rule::in($context->organization()->currencies())],
            'conditions' => ['nullable', 'string', 'max:5000'],
        ]);

        $tenant = Tenant::query()->findOrFail($data['tenant_id']);
        $unit = Unit::query()->findOrFail($data['unit_id']);
        $contract = $contracts->open(
            $context->organization(),
            $unit,
            $tenant,
            $request->user(),
            CarbonImmutable::parse($data['start_date']),
            $data['end_date'] ? CarbonImmutable::parse($data['end_date']) : null,
            Money::toMinor($data['rent']),
            $data['currency'],
            $data['conditions'] ?? null,
        );

        return redirect()->route('office.contracts.show', $contract)->with('status', 'Contrat créé.');
    }

    public function show(Contract $contract, ProrataManager $prorata)
    {
        $this->authorize('view', $contract);
        $contract->load('tenant', 'unit.property', 'invoices');

        return view('office.contracts.show', [
            'contract' => $contract,
            'prorata' => $prorata->options(),
        ]);
    }

    public function update(Request $request, Contract $contract, ContractService $contracts, ProrataManager $prorata)
    {
        $this->authorize('manage', $contract);
        $data = $request->validate([
            'end_date' => ['nullable', 'date'],
            'conditions' => ['nullable', 'string', 'max:5000'],
            'due_day' => ['required', 'integer', 'min:1', 'max:31'],
            'generation_day' => ['required', 'integer', 'min:1', 'max:28'],
            'grace_until_day' => ['required', 'integer', 'min:1', 'max:28'],
            'prorata_method' => ['required', Rule::in(array_keys($prorata->options()))],
        ]);
        $contracts->updateTerms($contract, $request->user(), $data);

        return back()->with('status', 'Conditions mises à jour. Le loyer historique est inchangé.');
    }
}
