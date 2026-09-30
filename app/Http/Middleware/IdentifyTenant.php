<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantFinderClass = config('multitenancy.tenant_finder');

        if (! $tenantFinderClass) {
            return $next($request);
        }

        $host = strtolower($request->getHost());

        // Landlord hosts run outside any tenant context.
        if (in_array($host, config('multitenancy.landlord_domains', []), true)) {
            app(IsTenant::class)::forgetCurrent();

            return $next($request);
        }

        /** @var TenantFinder $tenantFinder */
        $tenantFinder = app($tenantFinderClass);
        $tenant = $tenantFinder->findForRequest($request);

        if ($tenant instanceof IsTenant) {
            $tenant->makeCurrent();

            return $next($request);
        }

        // Every other host is unrecognized: fail closed instead of silently
        // falling back to the landlord surface.
        throw NoCurrentTenant::make();
    }
}
