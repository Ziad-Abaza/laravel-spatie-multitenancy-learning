<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLandlordContext
{
    /**
     * Landlord-area routes are only reachable on landlord hosts. IdentifyTenant
     * guarantees that requests reaching this point are either landlord or
     * tenant context, so a current tenant means the wrong surface.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(Tenant::checkCurrent(), 404);

        return $next($request);
    }
}
