<?php

namespace Modules\Access\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Modules\Access\Services\AuditWriter;

/**
 * Records authentication activity into the context-local audit trail —
 * `audit_logs` on tenant hosts, `admin_audit_logs` on landlord hosts. Never
 * logs credentials: the failed-login label is the submitted email only.
 */
class LogAuthActivity
{
    public function __construct(
        protected AuditWriter $audit
    ) {}

    public function handle(Login|Failed|Logout $event): void
    {
        try {
            $user = $event->user ?? null;
            $guard = $event->guard ?? 'web';

            if ($event instanceof Failed) {
                $this->audit->record(
                    $user, $guard, 'auth.login_failed', 'user', $user,
                    $user?->email ?? ($event->credentials['email'] ?? 'unknown'),
                );

                return;
            }

            $this->audit->record(
                $user, $guard,
                $event instanceof Login ? 'auth.login' : 'auth.logout',
                'user', $user,
            );
        } catch (\Throwable $e) {
            // Audit is a side effect — never break authentication.
            report($e);
        }
    }
}
