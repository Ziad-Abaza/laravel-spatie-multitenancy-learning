<?php

namespace Modules\Landlord\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

/**
 * Immutable audit record for platform access-control mutations.
 * actor/target identifiers are snapshots — no foreign keys, so the trail
 * survives deletion of the referenced rows.
 */
class AdminAuditLog extends Model
{
    use UsesLandlordConnection;

    protected $table = 'admin_audit_logs';

    public const UPDATED_AT = null;

    public const ACTIONS = [
        'admin.created',
        'admin.updated',
        'admin.suspended',
        'admin.reactivated',
        'admin.role_changed',
        'admin.password_reset',
        'admin.deleted',
        'role.created',
        'role.updated',
        'role.deleted',
        'system.repair',
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
