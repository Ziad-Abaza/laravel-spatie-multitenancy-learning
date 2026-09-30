<?php

namespace App\Http\Middleware;

use App\Exceptions\TenantSuspendedException;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    /**
     * Access control for tenant status: a resolved but non-active tenant
     * (suspended, archived) may not serve any request on its domain.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::current();

        if ($tenant && ! $tenant->isActive()) {
            throw TenantSuspendedException::make($tenant);
        }

        return $next($request);
    }
}
