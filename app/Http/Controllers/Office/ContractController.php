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
use App\Services\RecognitionService;
use App\Support\CurrentContext;
use App\Support\DeedPdf;
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
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ]);

        $contracts->accept($rentalRequest, $request->user(), [
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'rent_minor' => Money::toMinor($data['rent']),
            'currency' => $data['currency'],
            'conditions' => $data['conditions'] ?? null,
            'attachment_path' => store_upload($request->file('attachment'), 'contracts'),
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

        $organization = app(CurrentContext::class)->organization();

        return view('office.contracts.index', [
            'contracts' => $contracts,
            'tenants' => Tenant::query()->orderBy('name')->get(),
            'units' => Unit::query()->where('status', 'available')->orderBy('name')->get(),
            'currencies' => $organization->currencies(),
            'depositMonths' => (int) $organization->preference('guarantee_deposit_months', 3),
            'advanceMonths' => (int) $organization->preference('guarantee_advance_months', 1),
        ]);
    }

    public function store(Request $request, CurrentContext $context, ContractService $contracts)
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer'],
            'unit_id' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'rent' => ['required', 'string', 'max:20'],
            'currency' => ['required', Rule::in($context->organization()->currencies())],
            'conditions' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ]);

        $tenant = Tenant::query()->findOrFail($data['tenant_id']);
        $unit = Unit::query()->findOrFail($data['unit_id']);
        $contract = $contracts->open(
            $context->organization(),
            $unit,
            $tenant,
            $request->user(),
            CarbonImmutable::parse($data['start_date']),
            ($data['end_date'] ?? null) ? CarbonImmutable::parse($data['end_date']) : null,
            Money::toMinor($data['rent']),
            $data['currency'],
            $data['conditions'] ?? null,
            false,
            store_upload($request->file('attachment'), 'contracts'),
        );

        return redirect()->route('office.contracts.show', $contract)->with('status', 'Contrat créé.');
    }

    public function show(Contract $contract, ProrataManager $prorata)
    {
        $this->authorize('view', $contract);
        $contract->load('tenant', 'unit.property', 'invoices', 'recognitionDeed', 'organization');

        return view('office.contracts.show', [
            'contract' => $contract,
            'prorata' => $prorata->options(),
            'deedDefaults' => app(RecognitionService::class)->defaults($contract),
            'hasCertificate' => (string) $contract->organization->preference('certificate_code', '') !== '',
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

    public function storeDeed(Request $request, Contract $contract, RecognitionService $deeds)
    {
        $this->authorize('manage', $contract);
        $data = $request->validate([
            'payee_name' => ['required', 'string', 'max:160'],
            'deposit_months' => ['required', 'integer', 'min:0', 'max:24'],
            'advance_months' => ['required', 'integer', 'min:0', 'max:12'],
            'identity_document' => ['nullable', 'string', 'max:120'],
            'identity' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
            'origin' => ['nullable', 'string', 'max:160'],
            'premises' => ['required', 'string', 'max:255'],
            'landlord_witnesses' => ['nullable', 'string', 'max:500'],
            'tenant_witnesses' => ['nullable', 'string', 'max:500'],
            'certify' => ['required', 'boolean'],
        ]);
        $data['identity_path'] = store_upload($request->file('identity'), 'deeds');
        $deeds->record($contract, $request->user(), $data, $request->boolean('certify'));

        return back()->with('status', $request->boolean('certify')
            ? 'Acte de reconnaissance certifié. Le bailleur et le locataire ont chacun leur copie.'
            : 'Acte de reconnaissance enregistré. Il n\'est pas encore certifié.');
    }

    public function deed(Contract $contract)
    {
        $this->authorize('view', $contract);

        return view('documents.recognition', $this->deedView($contract, 'bailleur'));
    }

    public function deedPdf(Contract $contract, DeedPdf $pdf)
    {
        $this->authorize('view', $contract);
        $deed = $this->certifiedDeed($contract);

        return response($pdf->render($deed, 'bailleur'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$deed->reference.'-bailleur.pdf"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function deedView(Contract $contract, string $copy): array
    {
        $deed = $this->certifiedDeed($contract);
        $deed->load('contract.tenant', 'contract.unit.property', 'organization');

        return [
            'deed' => $deed,
            'copy' => $copy,
            'pdf' => $copy === 'locataire' ? route('portal.deed.pdf') : route('office.contracts.deed.pdf', $contract),
        ];
    }

    private function certifiedDeed(Contract $contract): \App\Models\RecognitionDeed
    {
        $deed = $contract->recognitionDeed()->first();

        abort_unless($deed?->isCertified(), 404);

        return $deed;
    }
}
