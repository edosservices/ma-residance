<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrganizationCounter;
use Illuminate\Database\UniqueConstraintViolationException;

class CounterService
{
    public function next(int $organizationId, string $key): int
    {
        $counter = OrganizationCounter::query()
            ->where('organization_id', $organizationId)
            ->where('key', $key)
            ->lockForUpdate()
            ->first();

        if ($counter === null) {
            try {
                $counter = OrganizationCounter::query()->create([
                    'organization_id' => $organizationId,
                    'key' => $key,
                    'value' => 1,
                ]);

                return 1;
            } catch (UniqueConstraintViolationException) {
                $counter = OrganizationCounter::query()
                    ->where('organization_id', $organizationId)
                    ->where('key', $key)
                    ->lockForUpdate()
                    ->firstOrFail();
            }
        }

        $counter->value++;
        $counter->save();

        return (int) $counter->value;
    }

    public function reference(int $organizationId, string $key, string $prefix): string
    {
        return sprintf('%s-%05d', $prefix, $this->next($organizationId, $key));
    }
}
