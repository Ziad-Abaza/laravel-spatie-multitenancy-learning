<?php

namespace Modules\Landlord\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Modules\Core\Enums\TenantStatus;
use Modules\Core\Events\TenantStatusChanged;

class TenantLifecycleService
{
    /**
     * Suspend an active tenant workspace.
     */
    public function suspend(Tenant $tenant, ?string $reason = null): Tenant
    {
        $oldStatus = $tenant->getStatus();

        $settings = $tenant->settings ?? [];
        if ($reason) {
            $settings['suspension_reason'] = $reason;
        }

        $tenant->update([
            'status' => TenantStatus::Suspended,
            'suspended_at' => now(),
            'settings' => $settings,
        ]);

        event(new TenantStatusChanged($tenant, $oldStatus, TenantStatus::Suspended->value));

        return $tenant;
    }

    /**
     * Activate or resume a suspended tenant workspace.
     */
    public function activate(Tenant $tenant): Tenant
    {
        $oldStatus = $tenant->getStatus();

        $settings = $tenant->settings ?? [];
        unset($settings['suspension_reason']);

        $tenant->update([
            'status' => TenantStatus::Active,
            'suspended_at' => null,
            'settings' => $settings,
        ]);

        event(new TenantStatusChanged($tenant, $oldStatus, TenantStatus::Active->value));

        return $tenant;
    }

    /**
     * Archive an inactive tenant.
     */
    public function archive(Tenant $tenant): Tenant
    {
        $oldStatus = $tenant->getStatus();

        $tenant->update([
            'status' => TenantStatus::Archived,
        ]);

        event(new TenantStatusChanged($tenant, $oldStatus, TenantStatus::Archived->value));

        return $tenant;
    }

    /**
     * Delete tenant and optionally drop the associated tenant database.
     */
    public function delete(Tenant $tenant, bool $dropDatabase = false): void
    {
        if ($dropDatabase && ! empty($tenant->database)) {
            $landlordConnection = config('multitenancy.landlord_database_connection_name', 'landlord');
            $driver = config("database.connections.{$landlordConnection}.driver", 'mysql');

            try {
                if ($driver === 'sqlite') {
                    // Under sqlite the tenant record stores the database file path.
                    if (is_file($tenant->database)) {
                        @unlink($tenant->database);
                    }
                } else {
                    DB::connection($landlordConnection)->statement("DROP DATABASE IF EXISTS `{$tenant->database}`");
                }
            } catch (\Throwable) {
                // Ignore DB drop error during cleanup
            }
        }

        $tenant->subscriptions()->delete();
        $tenant->delete();
    }
}
