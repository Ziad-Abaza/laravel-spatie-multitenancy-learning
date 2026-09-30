<?php

namespace Modules\Access\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Landlord\Models\AdminAuditLog;
use Spatie\Multitenancy\Models\Tenant;

/**
 * Writes append-only audit records for access-control mutations. Called from
 * controllers inside the same transaction as the mutation — the policy layer
 * stays pure.
 */
class AuditWriter
{
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
        // AdminAuditLog lives on the landlord connection; inside a tenant
        // context transaction the write would escape that transaction's
        // atomicity. The audit surface is landlord-only by design — fail
        // loudly rather than silently split a mutation from its record.
        throw_if(Tenant::checkCurrent(), \LogicException::class, 'AuditWriter is landlord-context only.');

        AdminAuditLog::create([
            'actor_guard' => $guard,
            'actor_id' => $actor?->getAuthIdentifier(),
            'actor_label' => $actor?->email ?? 'console',
            'action' => $action,
            'target_kind' => $targetKind,
            'target_id' => $target?->getKey(),
            'target_label' => $targetLabel ?? $target?->email ?? $target?->name,
            'before' => $before,
            'after' => $after,
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
