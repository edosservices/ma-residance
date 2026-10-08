<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isActive() || ! $user->is_super_admin) {
            abort(403);
        }

        view()->share([
            'shellNav' => 'partials.nav-admin',
            'home' => route('admin.dashboard'),
            'alerts' => route('admin.audit'),
            'eyebrow' => 'Administration',
        ]);

        return $next($request);
    }
}
