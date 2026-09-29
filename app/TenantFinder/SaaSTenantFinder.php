<?php

namespace App\TenantFinder;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class SaaSTenantFinder extends TenantFinder
{
    /**
     * Resolve the current tenant from the given request.
     */
    public function findForRequest(Request $request): ?IsTenant
    {
        /** @var class-string<IsTenant&Model> $tenantModel */
        $tenantModel = config('multitenancy.tenant_model', Tenant::class);

        $host = strtolower($request->getHost());

        // Landlord host definitions
        $landlordHosts = array_filter([
            'localhost',
            '127.0.0.1',
            '::1',
            strtolower((string) parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST)),
            strtolower((string) config('multitenancy.landlord_domain')),
        ]);

        $isLandlordHost = in_array($host, $landlordHosts, true);

        // Allow explicit header or query parameter for testing or API clients
        $tenantIdentifier = $request->header('X-Tenant')
            ?? $request->header('x-tenant')
            ?? $request->server('HTTP_X_TENANT')
            ?? ($request->has('tenant') ? (string) $request->query('tenant') : null);

        if ($tenantIdentifier) {
            return $tenantModel::query()
                ->where('id', $tenantIdentifier)
                ->orWhere('domain', $tenantIdentifier)
                ->orWhere('slug', $tenantIdentifier)
                ->first();
        }

        if ($isLandlordHost) {
            return null;
        }

        // 1. Direct match on domain
        $tenant = $tenantModel::where('domain', $host)->first();
        if ($tenant) {
            return $tenant;
        }

        // 2. Subdomain extraction (e.g. tenant1.localhost or tenant1.app.test)
        $parts = explode('.', $host);
        if (count($parts) >= 2) {
            $subdomain = $parts[0];
            $tenant = $tenantModel::query()
                ->where('slug', $subdomain)
                ->orWhere('domain', $subdomain)
                ->orWhere('domain', "{$subdomain}.localhost")
                ->first();

            if ($tenant) {
                return $tenant;
            }
        }

        return null;
    }
}
