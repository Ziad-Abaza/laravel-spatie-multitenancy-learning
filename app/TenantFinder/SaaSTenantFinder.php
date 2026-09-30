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
     * Resolve the tenant for the given request. Pure lookup only: exact
     * domain match first (covers platform subdomains and custom domains),
     * then slug matching under the configured tenant domain suffix.
     * Admission policy (landlord hosts, unknown hosts) is handled by the
     * IdentifyTenant middleware; tenant status by EnsureTenantIsActive.
     */
    public function findForRequest(Request $request): ?IsTenant
    {
        /** @var class-string<IsTenant&Model> $tenantModel */
        $tenantModel = config('multitenancy.tenant_model', Tenant::class);

        $host = strtolower($request->getHost());

        $tenant = $tenantModel::query()->where('domain', $host)->first();

        if ($tenant) {
            return $tenant;
        }

        $suffix = $this->tenantDomainSuffix();

        if ($suffix !== null && str_ends_with($host, '.'.$suffix)) {
            $subdomain = explode('.', $host)[0];

            return $tenantModel::query()->where('slug', $subdomain)->first();
        }

        return null;
    }

    protected function tenantDomainSuffix(): ?string
    {
        $suffix = config('multitenancy.tenant_domain_suffix');

        return is_string($suffix) && $suffix !== '' ? strtolower($suffix) : null;
    }
}
