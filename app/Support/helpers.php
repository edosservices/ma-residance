<?php

use App\Enums\Permission;
use App\Support\CurrentContext;
use App\Support\Money;
use Illuminate\Http\UploadedFile;

function money(int $minor, string $currency): string
{
    return Money::format($minor, $currency);
}

function store_upload(?UploadedFile $file, string $folder): ?string
{
    return $file?->store($folder, 'public');
}

function allows(string $permission): bool
{
    $member = app(CurrentContext::class)->member();

    if ($member === null) {
        return false;
    }

    return $member->hasPermission(Permission::from($permission));
}
