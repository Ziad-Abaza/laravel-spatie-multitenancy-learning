<?php

namespace Modules\Access\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

/**
 * Immutable audit record for tenant-side mutations and auth events.
 * Actor/target identifiers are snapshots — no foreign keys, so the trail
 * survives deletion of the referenced rows.
 */
class AuditLog extends Model
{
    use UsesTenantConnection;

    protected $table = 'audit_logs';

    public const UPDATED_AT = null;

    public const ACTIONS = [
        'user.created',
        'user.updated',
        'user.deleted',
        'role.created',
        'auth.login',
        'auth.login_failed',
        'auth.logout',
        'auth.sessions_revoked',
        'settings.updated',
    ];

    protected $fillable = [
        'actor_guard',
        'actor_id',
        'actor_label',
        'action',
        'target_kind',
        'target_id',
        'target_label',
        'before',
        'after',
        'ip',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
