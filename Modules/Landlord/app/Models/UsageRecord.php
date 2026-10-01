<?php

namespace Modules\Landlord\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

/**
 * Point-in-time usage snapshot per tenant — append-only time series written
 * by the scheduled metering command. Values are aggregates, never user-level
 * data.
 */
class UsageRecord extends Model
{
    use UsesLandlordConnection;

    protected $table = 'usage_records';

    public $timestamps = false;

    public const METRIC_USERS = 'users.count';

    public const METRIC_STORAGE_BYTES = 'storage.bytes';

    public const METRICS = [
        self::METRIC_USERS,
        self::METRIC_STORAGE_BYTES,
    ];

    protected $fillable = [
        'tenant_id',
        'metric',
        'value',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'recorded_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
