<?php

namespace Modules\Landlord\Listeners;

use Illuminate\Support\Facades\Auth;
use Modules\Access\Services\AuditWriter;
use Modules\Core\Events\TenantCreated;
use Modules\Core\Events\TenantStatusChanged;

/**
 * Mirrors lifecycle transitions into `admin_audit_logs`. Runs on the
 * landlord side for both admin-driven actions and the scheduled
 * `tenants:enforce-lifecycle` command (actor resolves to 'console').
 */
class AuditTenantLifecycle
{
    public function __construct(
        protected AuditWriter $audit
    ) {}

    public function handle(TenantCreated|TenantStatusChanged $event): void
    {
        try {
            if ($event instanceof TenantCreated) {
                $this->audit->record(
                    Auth::guard('landlord')->user(),
                    'landlord',
                    'tenant.created',
                    'tenant',
                    $event->tenant,
                    $event->tenant->name,
                    after: ['slug' => $event->tenant->slug, 'plan_id' => $event->planId],
                );

                return;
            }

            $this->audit->record(
                Auth::guard('landlord')->user(),
                'landlord',
                'tenant.status_changed',
                'tenant',
                $event->tenant,
                $event->tenant->name,
                before: ['status' => $event->oldStatus],
                after: ['status' => $event->newStatus],
            );
        } catch (\Throwable $e) {
            // Audit is a side effect — never break the lifecycle operation.
            report($e);
        }
    }
}
