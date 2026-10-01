<?php

namespace Modules\Landlord\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

/**
 * Registry row for a tenant database dump. Files live landlord-side under
 * `storage/app/backups/{slug}/` — never served to tenant hosts. Registry
 * rows cascade with the tenant; file cleanup happens in the service.
 */
class TenantBackup extends Model
{
    use UsesLandlordConnection;

    protected $table = 'tenant_backups';

    public const UPDATED_AT = null;

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id',
        'driver',
        'path',
        'size',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function absolutePath(): string
    {
        return storage_path('app/'.$this->path);
    }
}
