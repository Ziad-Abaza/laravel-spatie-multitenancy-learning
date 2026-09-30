<?php

namespace Modules\Landlord\Services;

use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Enums\TenantStatus;
use Modules\Core\Events\TenantStatusChanged;

class TenantLifecycleService
{
    /**
     * Guard a lifecycle transition against the TenantStatus state machine.
     *
     * @throws ValidationException when the transition is not allowed
     */
    protected function assertTransition(Tenant $tenant, TenantStatus $to): void
    {
        $from = TenantStatus::from($tenant->getStatus());

        throw_unless(
            $from->canTransitionTo($to),
            ValidationException::withMessages([
                'status' => [__('tenant_invalid_transition', ['from' => $from->value, 'to' => $to->value])],
            ])
        );
    }

    /**
     * Suspend an active tenant workspace.
     */
    public function suspend(Tenant $tenant, ?string $reason = null): Tenant
    {
        $this->assertTransition($tenant, TenantStatus::Suspended);

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
        $this->assertTransition($tenant, TenantStatus::Active);

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
        $this->assertTransition($tenant, TenantStatus::Archived);

        $oldStatus = $tenant->getStatus();

        $tenant->update([
            'status' => TenantStatus::Archived,
        ]);

        event(new TenantStatusChanged($tenant, $oldStatus, TenantStatus::Archived->value));

        return $tenant;
    }

    /**
     * Extend the tenant's trial window. Only valid while the tenant is on a
     * trialing/active status — a suspended tenant must be reactivated and an
     * archived tenant is terminal.
     */
    public function extendTrial(Tenant $tenant, Carbon $trialEndsAt): Tenant
    {
        $this->assertTransition($tenant, TenantStatus::Trialing);

        $tenant->update([
            'trial_ends_at' => $trialEndsAt,
            'status' => TenantStatus::Trialing,
        ]);

        $tenant->currentSubscription?->update(['trial_ends_at' => $trialEndsAt]);

        return $tenant;
    }

    /**
     * Delete tenant and optionally drop the associated tenant database.
     * Row-level deletion is atomic on the landlord connection; a failed
     * database drop surfaces as an exception before any row is removed.
     */
    public function delete(Tenant $tenant, bool $dropDatabase = false): void
    {
        if ($dropDatabase && ! empty($tenant->database)) {
            $this->dropTenantDatabase($tenant->database);
        }

        $landlordConnection = config('multitenancy.landlord_database_connection_name', 'landlord');

        DB::connection($landlordConnection)->transaction(function () use ($tenant) {
            $tenant->subscriptions()->delete();
            $tenant->delete();
        });
    }

    /**
     * Drop a tenant database (sqlite file path or MySQL schema name).
     * Failures throw — callers decide how to handle them.
     */
    public function dropTenantDatabase(string $database): void
    {
        $landlordConnection = config('multitenancy.landlord_database_connection_name', 'landlord');
        $driver = config("database.connections.{$landlordConnection}.driver", 'mysql');

        if ($driver === 'sqlite') {
            // Under sqlite the tenant record stores the database file path.
            if (is_file($database) && ! unlink($database)) {
                throw new \RuntimeException("Failed to delete tenant database file [{$database}].");
            }

            return;
        }

        DB::connection($landlordConnection)->statement("DROP DATABASE IF EXISTS `{$database}`");
    }
}
