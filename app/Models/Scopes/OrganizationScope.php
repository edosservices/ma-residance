<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Support\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $organizationId = app(CurrentContext::class)->organizationId();

        if ($organizationId !== null) {
            $builder->where($model->getTable().'.organization_id', $organizationId);
        }
    }
}
