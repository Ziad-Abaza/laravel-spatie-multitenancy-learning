<?php

namespace Modules\Landlord\Services;

use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Access\Services\AuditWriter;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Core\Enums\TenantStatus;
use Modules\Core\Events\TenantStatusChanged;

class TenantLifecycleService
{
    public function __construct(
        protected TenantBackupService $backups,
        protected SettingManagerContract $settingsManager,
        protected AuditWriter $audit
    ) {}

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
     * Schedule erasure of an archived tenant: mark the request, then give the
     * workspace a governed grace window (`system.retention_grace_days`)
     * before the sweep permanently purges it. Archived-only by design —
     * erasure of an operating workspace is never a valid request.
     */
    public function requestErasure(Tenant $tenant): Tenant
    {
        throw_unless(
            $tenant->getStatus() === TenantStatus::Archived->value,
            ValidationException::withMessages([
                'status' => [__('tenant_erasure_requires_archived')],
            ])
        );

        $graceDays = (int) $this->settingsManager->get('retention_grace_days', 30, 'system');
        $graceDays = $graceDays > 0 ? $graceDays : 30;

        $settings = $tenant->settings ?? [];
        $settings['erasure_requested_at'] = now()->toIso8601String();
        $settings['retention_until'] = now()->addDays($graceDays)->toIso8601String();

        $tenant->update(['settings' => $settings]);

        $this->audit->record(
            auth('landlord')->user(), 'landlord', 'tenant.erasure_requested', 'tenant', $tenant,
            $tenant->name,
            after: ['retention_until' => $settings['retention_until']],
        );

        return $tenant;
    }

    /**
     * Cancel a pending erasure before the grace window lapses.
     */
    public function cancelErasure(Tenant $tenant): Tenant
    {
        $wasRequested = isset(($tenant->settings ?? [])['erasure_requested_at']);

        $settings = $tenant->settings ?? [];
        unset($settings['erasure_requested_at'], $settings['retention_until']);

        $tenant->update(['settings' => $settings]);

        if ($wasRequested) {
            $this->audit->record(
                auth('landlord')->user(), 'landlord', 'tenant.erasure_canceled', 'tenant', $tenant,
                $tenant->name,
            );
        }

        return $tenant;
    }

    /**
     * Permanently purge tenants whose retention window has elapsed: a final
     * backup is exported, then the database and rows are dropped.
     *
     * @return int number of tenants purged
     */
    public function purgeExpiredRetentions(): int
    {
        $purged = 0;

        Tenant::query()
            ->where('status', TenantStatus::Archived->value)
            ->get()
            ->filter(function (Tenant $tenant) {
                $until = $tenant->settings['retention_until'] ?? null;

                return $until !== null
                    && isset($tenant->settings['erasure_requested_at'])
                    && \Illuminate\Support\Carbon::parse($until)->isPast();
            })
            ->each(function (Tenant $tenant) use (&$purged) {
                // Full erasure: database, row, AND backup artifacts. A dump
                // created here would be deleted by delete()'s dir purge
                // anyway — retention artifacts are the admin's job *before*
                // requesting erasure, not a hidden last-copy.
                $this->delete($tenant, dropDatabase: true);
                $purged++;
            });

        return $purged;
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

        $snapshot = [
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'domain' => $tenant->domain,
            'status' => $tenant->getStatus(),
        ];

        DB::connection($landlordConnection)->transaction(function () use ($tenant) {
            $tenant->subscriptions()->delete();
            $tenant->delete();
        });

        $this->audit->record(
            auth('landlord')->user(), 'landlord', 'tenant.deleted', 'tenant', null,
            $snapshot['name'],
            before: $snapshot + ['id' => $tenant->id, 'database_dropped' => $dropDatabase],
        );

        // Registry rows cascade with the tenant; artifacts must not outlive
        // them — purge the per-tenant backup directory after the row is gone.
        $this->backups->purgeTenantDirectory($tenant);
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
