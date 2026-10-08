<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Support\CurrentContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function __construct(private CurrentContext $context) {}

    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $member = $this->context->member();

        if ($member === null) {
            abort(403);
        }

        foreach (explode('|', $permissions) as $permission) {
            if ($member->hasPermission(Permission::from($permission))) {
                return $next($request);
            }
        }

        abort(403, 'Action non autorisée.');
    }
}
