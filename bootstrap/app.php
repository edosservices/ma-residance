<?php

use App\Http\Middleware\ClearOrganizationContext;
use App\Http\Middleware\PreventCachedPrivatePages;
use App\Http\Middleware\EnsureOrganization;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenant;
use App\Support\DomainException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(ClearOrganizationContext::class);
        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            EnsureOrganization::class,
        );
        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            EnsureTenant::class,
        );
        $middleware->alias([
            'org' => EnsureOrganization::class,
            'perm' => EnsurePermission::class,
            'tenant' => EnsureTenant::class,
            'super' => EnsureSuperAdmin::class,
            'private' => PreventCachedPrivatePages::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (DomainException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->with('error', $exception->getMessage());
        });
    })->create();
