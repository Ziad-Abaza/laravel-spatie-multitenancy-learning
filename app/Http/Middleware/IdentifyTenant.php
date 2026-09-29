<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        $tenantFinderClass = config('multitenancy.tenant_finder');

        if ($tenantFinderClass) {
            /** @var TenantFinder $tenantFinder */
            $tenantFinder = app($tenantFinderClass);
            $tenant = $tenantFinder->findForRequest($request);

            if ($tenant instanceof IsTenant) {
                $tenant->makeCurrent();
            } else {
                app(IsTenant::class)::forgetCurrent();
            }
        }

        return $next($request);
    }
}
