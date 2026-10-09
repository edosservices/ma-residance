<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\RecognitionDeed;
use App\Models\User;
use App\Support\DomainException;
use Illuminate\Support\Facades\DB;

class RecognitionService
{
    public function __construct(
        private CounterService $counters,
        private AuditLogger $audit,
        private NotificationDispatcher $notifications,
    ) {}

    /**
     * @return array{payee_name: string, deposit_months: int, advance_months: int, premises: string}
     */
    public function defaults(Contract $contract): array
    {
        $contract->loadMissing('organization', 'unit.property', 'tenant');
        $organization = $contract->organization;
        $owner = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('role', 'owner')
            ->with('user')
            ->first();

        $property = $contract->unit->property;

        return [
            'payee_name' => $owner?->user?->name ?: $organization->name,
            'deposit_months' => $contract->guaranteeDepositMonths(),
            'advance_months' => $contract->guaranteeAdvanceMonths(),
            'premises' => collect([
                $property?->name,
                $contract->unit->name,
                $property?->address,
                $property?->city,
            ])->filter(fn ($part) => is_string($part) && $part !== '')->implode(' · '),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(Contract $contract, User $actor, array $data, bool $certify): RecognitionDeed
    {
        return DB::transaction(function () use ($contract, $actor, $data, $certify) {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            $existing = RecognitionDeed::query()->where('contract_id', $contract->id)->lockForUpdate()->first();

            if ($existing?->isCertified()) {
                throw new DomainException('Cet acte de reconnaissance est déjà certifié.');
            }

            $deposit = (int) $data['deposit_months'];
            $advance = (int) $data['advance_months'];

            if ($deposit + $advance < 1) {
                throw new DomainException('Indiquez au moins un mois de garantie ou d\'avance.');
            }

            $organization = Organization::query()->findOrFail($contract->organization_id);
            $payload = [
                'payee_name' => $data['payee_name'],
                'deposit_months' => $deposit,
                'advance_months' => $advance,
                'amount_minor' => $contract->rent_minor * ($deposit + $advance),
                'currency' => $contract->currency,
                'identity_document' => $data['identity_document'] ?? null,
                'origin' => $data['origin'] ?? null,
                'premises' => $data['premises'],
                'landlord_witnesses' => $data['landlord_witnesses'] ?? null,
                'tenant_witnesses' => $data['tenant_witnesses'] ?? null,
            ];

            if (! empty($data['identity_path'])) {
                $payload['identity_path'] = $data['identity_path'];
            }

            if ($existing === null) {
                $deed = new RecognitionDeed($payload + [
                    'organization_id' => $organization->id,
                    'contract_id' => $contract->id,
                    'reference' => $this->counters->reference($organization->id, 'deed', 'ACT'),
                    'created_by' => $actor->id,
                ]);
            } else {
                $deed = $existing->fill($payload);
            }

            if ($certify) {
                $this->applyCertificate($deed, $organization);
            }

            $deed->save();

            $this->audit->log(
                $organization->id,
                $actor,
                $certify ? 'deed.certified' : 'deed.saved',
                $deed,
                $certify
                    ? "A certifié l'acte de reconnaissance {$deed->reference}."
                    : "A préparé l'acte de reconnaissance {$deed->reference}.",
            );

            if ($certify) {
                $contract->load('tenant.user');
                $tenantUser = $contract->tenant?->user;
                if ($tenantUser !== null) {
                    $this->notifications->notify(
                        $tenantUser,
                        'deed.certified',
                        'Acte de reconnaissance',
                        "Votre copie de l'acte {$deed->reference} est disponible.",
                        route('portal.contract'),
                    );
                }
            }

            return $deed;
        });
    }

    private function applyCertificate(RecognitionDeed $deed, Organization $organization): void
    {
        $code = (string) $organization->preference('certificate_code', '');
        $path = (string) $organization->preference('certificate_path', '');
        $holder = (string) $organization->preference('certificate_holder', '');

        if ($code === '' || $path === '' || $holder === '') {
            throw new DomainException('Ajoutez d\'abord le certificat numérique du bailleur dans les paramètres.');
        }

        $deed->certified_at = now();
        $deed->certificate_code = $code;
        $deed->certificate_holder = $holder;
        $deed->certificate_path = $path;
        $deed->content_hash = hash('sha256', implode('|', [
            $deed->reference ?? '',
            $deed->payee_name,
            (string) $deed->deposit_months,
            (string) $deed->advance_months,
            (string) $deed->amount_minor,
            $deed->currency,
            (string) $deed->identity_document,
            (string) $deed->origin,
            $deed->premises,
            (string) $deed->landlord_witnesses,
            (string) $deed->tenant_witnesses,
            $code,
            $holder,
        ]));
    }
}
