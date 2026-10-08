<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceUrgency;
use App\Enums\Permission;
use App\Models\ExpenseCategory;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceUpdate;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use App\Support\DomainException;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    public function __construct(
        private AuditLogger $audit,
        private NotificationDispatcher $notifications,
        private ExpenseService $expenses,
    ) {}

    public function report(Organization $organization, Unit $unit, User $actor, array $data, ?int $tenantId = null): MaintenanceRequest
    {
        $request = MaintenanceRequest::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenantId,
            'reported_by' => $actor->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'urgency' => $data['urgency'] ?? MaintenanceUrgency::Normal,
            'status' => MaintenanceStatus::Reported,
            'photo_path' => $data['photo_path'] ?? null,
            'currency' => $data['currency'] ?? $organization->preference('default_currency'),
        ]);

        $this->logUpdate($request, $actor, MaintenanceStatus::Reported, $data['description'], $data['photo_path'] ?? null);

        $this->notifications->notifyMembers(
            $organization,
            Permission::MaintenanceManage,
            'maintenance.reported',
            'Incident signalé',
            $request->title.' — '.$unit->name,
            route('office.maintenance.show', $request),
        );

        $this->audit->log($organization->id, $actor, 'maintenance.reported', $request, 'A signalé : '.$request->title.'.');

        return $request;
    }

    public function advance(MaintenanceRequest $request, User $actor, MaintenanceStatus $to, array $data = []): MaintenanceRequest
    {
        return DB::transaction(function () use ($request, $actor, $to, $data) {
            $request = MaintenanceRequest::withoutGlobalScopes()->lockForUpdate()->findOrFail($request->id);

            if (! in_array($to, $request->status->next(), true)) {
                throw new DomainException('Cette étape n\'est pas autorisée.');
            }

            if ($to === MaintenanceStatus::Quoted) {
                if (empty($data['estimated_cost_minor'])) {
                    throw new DomainException('Le devis doit indiquer un coût estimé.');
                }

                $request->estimated_cost_minor = (int) $data['estimated_cost_minor'];
                $request->currency = $data['currency'] ?? $request->currency;
                $request->quote_note = $data['note'] ?? null;
            }

            if (! empty($data['assigned_to'])) {
                $request->assigned_to = $data['assigned_to'];
            }

            $expense = null;

            if ($to === MaintenanceStatus::Done) {
                if (empty($data['actual_cost_minor'])) {
                    throw new DomainException('Indiquez le coût réel de l\'intervention.');
                }

                $organization = Organization::query()->findOrFail($request->organization_id);
                $category = ExpenseCategory::withoutGlobalScopes()
                    ->where('organization_id', $organization->id)
                    ->where('slug', 'maintenance')
                    ->first();

                if ($category === null) {
                    throw new DomainException('La catégorie Maintenance est introuvable.');
                }

                $unit = Unit::withoutGlobalScopes()->findOrFail($request->unit_id);
                $expense = $this->expenses->record($organization, $actor, [
                    'expense_category_id' => $category->id,
                    'property_id' => $unit->property_id,
                    'unit_id' => $unit->id,
                    'tenant_id' => $request->tenant_id,
                    'maintenance_request_id' => $request->id,
                    'amount_minor' => (int) $data['actual_cost_minor'],
                    'currency' => $data['currency'] ?? $request->currency ?? $organization->preference('default_currency'),
                    'spent_on' => now()->toDateString(),
                    'payee' => $data['payee'] ?? null,
                    'motif' => $request->title,
                    'comment' => $data['note'] ?? null,
                ]);
            }

            $request->status = $to;
            $request->save();
            $this->logUpdate($request, $actor, $to, $data['note'] ?? null, $data['photo_path'] ?? null, $expense?->id);

            $this->audit->log(
                $request->organization_id,
                $actor,
                'maintenance.'.$to->value,
                $request,
                'A passé l\'incident « '.$request->title.' » à '.$to->label().'.',
            );

            $request->load('reporter');
            if ($request->reporter && $request->reporter->id !== $actor->id) {
                $request->loadMissing('tenant');
                $portal = $request->tenant && (int) $request->tenant->user_id === (int) $request->reported_by;
                $this->notifications->notify(
                    $request->reporter,
                    'maintenance.updated',
                    'Incident mis à jour',
                    $request->title.' : '.$to->label().'.',
                    $portal ? route('portal.maintenance.show', $request) : route('office.maintenance.show', $request),
                );
            }

            return $request;
        });
    }

    public function comment(MaintenanceRequest $request, User $actor, string $note, ?string $photo = null): void
    {
        $this->logUpdate($request, $actor, null, $note, $photo);
    }

    private function logUpdate(MaintenanceRequest $request, User $actor, ?MaintenanceStatus $status, ?string $note, ?string $photo, ?int $expenseId = null): void
    {
        MaintenanceUpdate::withoutGlobalScopes()->create([
            'organization_id' => $request->organization_id,
            'maintenance_request_id' => $request->id,
            'user_id' => $actor->id,
            'status_to' => $status?->value,
            'note' => $note,
            'photo_path' => $photo,
            'expense_id' => $expenseId,
        ]);
    }
}
