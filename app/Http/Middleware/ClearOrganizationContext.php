<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\CurrentContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClearOrganizationContext
{
    public function __construct(private CurrentContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->clear();

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $this->context->clear();
    }
}
