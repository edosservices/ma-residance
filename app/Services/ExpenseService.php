<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Organization;
use App\Models\User;
use App\Support\DomainException;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(private AuditLogger $audit) {}

    public function record(Organization $organization, User $actor, array $data): Expense
    {
        $category = ExpenseCategory::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->find($data['expense_category_id']);

        if ($category === null) {
            throw new DomainException('Catégorie introuvable.');
        }

        $expense = Expense::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'expense_category_id' => $category->id,
            'property_id' => $data['property_id'] ?? null,
            'unit_id' => $data['unit_id'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'maintenance_request_id' => $data['maintenance_request_id'] ?? null,
            'amount_minor' => $data['amount_minor'],
            'currency' => $data['currency'],
            'spent_on' => $data['spent_on'],
            'payee' => $data['payee'] ?? null,
            'motif' => $data['motif'],
            'comment' => $data['comment'] ?? null,
            'attachment_path' => $data['attachment_path'] ?? null,
            'status' => ExpenseStatus::Recorded,
            'recorded_by' => $actor->id,
        ]);

        $this->audit->log(
            $organization->id,
            $actor,
            'expense.recorded',
            $expense,
            'A enregistré une dépense de '.money((int) $expense->amount_minor, $expense->currency).' : '.$expense->motif.'.',
        );

        return $expense;
    }

    public function void(Expense $expense, User $actor, string $reason): Expense
    {
        return DB::transaction(function () use ($expense, $actor, $reason) {
            $expense = Expense::withoutGlobalScopes()->lockForUpdate()->findOrFail($expense->id);

            if ($expense->status === ExpenseStatus::Voided) {
                return $expense;
            }

            $expense->status = ExpenseStatus::Voided;
            $expense->voided_at = now();
            $expense->void_reason = $reason;
            $expense->save();

            $this->audit->log($expense->organization_id, $actor, 'expense.voided', $expense, 'A annulé la dépense « '.$expense->motif.' ». '.$reason);

            return $expense;
        });
    }
}
