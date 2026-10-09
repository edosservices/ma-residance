<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExpenseStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'expense_category_id', 'property_id', 'unit_id', 'tenant_id',
        'maintenance_request_id', 'amount_minor', 'currency', 'spent_on', 'payee', 'motif',
        'comment', 'attachment_path', 'status', 'voided_at', 'void_reason', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExpenseStatus::class,
            'amount_minor' => 'integer',
            'spent_on' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Expense $expense) {
            if ($expense->isDirty('amount_minor') || $expense->isDirty('currency')) {
                throw new DomainException('Le montant d\'une dépense est immuable.');
            }
        });

        static::deleting(fn () => throw new DomainException('Une dépense ne peut pas être supprimée.'));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
