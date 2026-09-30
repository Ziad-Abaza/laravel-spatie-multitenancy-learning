<?php

namespace App\Support;

use App\Models\Tenant;
use Modules\Landlord\Models\Tenant as BaseLandlordTenant;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

/**
 * Scopes media storage paths to the owning tenant so one tenant's uploads are
 * never enumerable alongside another tenant's on the shared public disk.
 *
 * - Media owned by the landlord Tenant record itself (workspace logo) is
 *   prefixed by the owner id — the path resolves identically in landlord and
 *   tenant context, because the owner is constant, not ambient.
 * - Media owned by tenant-side models (users, ...) is prefixed by the current
 *   tenant — those rows only ever surface while that tenant is current.
 * - Landlord-owned media keeps the unprefixed default path.
 */
class TenantAwarePathGenerator extends DefaultPathGenerator
{
    protected function getBasePath(Media $media): string
    {
        $base = parent::getBasePath($media);

        if (is_a($media->model_type, BaseLandlordTenant::class, true)) {
            return "tenants/{$media->model_id}/{$base}";
        }

        $tenantKey = Tenant::current()?->getKey();

        return $tenantKey === null ? $base : "tenants/{$tenantKey}/{$base}";
    }
}
