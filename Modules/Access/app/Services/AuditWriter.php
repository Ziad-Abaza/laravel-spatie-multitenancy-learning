<?php

namespace Modules\Access\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Access\Models\AuditLog;
use Modules\Landlord\Models\AdminAuditLog;
use Spatie\Multitenancy\Models\Tenant;

/**
 * Append-only audit writer. The record lands on whichever connection owns the
 * current context — `audit_logs` (tenant) or `admin_audit_logs` (landlord) —
 * so writes stay inside the mutation's transaction instead of escaping it.
 * Called from controllers inside the same transaction as the mutation — the
 * policy layer stays pure.
 */
class AuditWriter
{
    /**
     * Keys that must never be persisted in before/after snapshots.
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'remember_token',
        'token',
        'secret',
    ];

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        ?Authenticatable $actor,
        string $guard,
        string $action,
        string $targetKind,
        ?Model $target,
        ?string $targetLabel = null,
        ?array $before = null,
        ?array $after = null,
    ): void {
        $model = Tenant::checkCurrent() ? new AuditLog : new AdminAuditLog;

        $model->forceFill([
            'actor_guard' => $guard,
            'actor_id' => $actor?->getAuthIdentifier(),
            'actor_label' => $actor?->email ?? 'console',
            'action' => $action,
            'target_kind' => $targetKind,
            'target_id' => $target?->getKey(),
            'target_label' => $targetLabel ?? $target?->email ?? $target?->name,
            'before' => $this->redact($before),
            'after' => $this->redact($after),
            'ip' => request()?->ip(),
            'created_at' => now(),
        ])->save();
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>|null
     */
    private function redact(?array $snapshot): ?array
    {
        if ($snapshot === null) {
            return null;
        }

        return collect($snapshot)
            ->mapWithKeys(fn ($value, $key) => [
                $key => in_array($key, self::SENSITIVE_KEYS, true) || str_contains((string) $key, 'password')
                    ? '[redacted]'
                    : $value,
            ])
            ->all();
    }
}
