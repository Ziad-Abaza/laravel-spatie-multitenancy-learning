<?php

use App\Exceptions\TenantSuspendedException;
use App\Http\Middleware\EnsureLandlordAdminActive;
use App\Http\Middleware\EnsureLandlordContext;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\EnsureTenantUserActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\SetLocale;
use App\Support\ErrorPageRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;
use Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            IdentifyTenant::class,
            EnsureTenantIsActive::class,
        ], append: [
            // Enables Auth::logoutOtherDevices — the session carries the
            // password hash fingerprint, so a password change invalidates
            // every other session without enumerating shared session rows.
            AuthenticateSession::class,
            SetLocale::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->api(prepend: [
            IdentifyTenant::class,
            EnsureTenantIsActive::class,
        ]);

        $middleware->group('tenant', [
            NeedsTenant::class,
            EnsureValidTenantSession::class,
            EnsureTenantUserActive::class,
        ]);

        $middleware->alias([
            'landlord' => EnsureLandlordContext::class,
            'landlord.active' => EnsureLandlordAdminActive::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (NoCurrentTenant $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => __('error_tenant_not_found'),
                ], 404);
            }

            // Web requests fall through to the unified error page in respond() below.
        });

        $exceptions->render(function (TenantSuspendedException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => __('tenant_suspended_message'),
                ], 423);
            }

            // Web requests fall through to the unified error page in respond() below.
        });

        $exceptions->respond(
            fn (Response $response, Throwable $e, Request $request) => app(ErrorPageRenderer::class)->respond($response, $e, $request)
        );
    })->create();
