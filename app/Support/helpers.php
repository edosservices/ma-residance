<?php

use App\Enums\MemberRole;
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
    return $file?->store($folder, 'local');
}

function file_url(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }

    return route('files.show', ['path' => $path]);
}

function allows(string $permission): bool
{
    $member = app(CurrentContext::class)->member();

    if ($member === null) {
        return false;
    }

    return $member->hasPermission(Permission::from($permission));
}

function sees_trace(): bool
{
    $context = app(CurrentContext::class);
    $member = $context->member();

    if ($member !== null) {
        if (in_array($member->role, [MemberRole::Owner, MemberRole::Manager], true)) {
            return true;
        }

        return $member->hasPermission(Permission::TracesShare);
    }

    if ($context->organizationId() === null) {
        return false;
    }

    return (bool) $context->organization()->preference('share_declaration_trace', false);
}
