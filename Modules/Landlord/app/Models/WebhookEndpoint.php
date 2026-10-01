<?php

namespace Modules\Landlord\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

/**
 * Registered outbound webhook target. The secret signs every payload with
 * HMAC-SHA256 — receivers verify `X-Webhook-Signature` against the raw body.
 */
class WebhookEndpoint extends Model
{
    use UsesLandlordConnection;

    protected $table = 'webhook_endpoints';

    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'active' => 'boolean',
        ];
    }

    /**
     * The domain events this endpoint subscribes to — the dispatch-side
     * event keys are kebab-cased class basenames (e.g. `tenant.status-changed`).
     *
     * @return array<int, string>
     */
    public static function supportedEvents(): array
    {
        return [
            'tenant.created',
            'tenant.provisioned',
            'tenant.status-changed',
            'plan.changed',
            'subscription.created',
            'subscription.updated',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'webhook_endpoint_id');
    }

    public function signatureFor(string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, $this->secret);
    }
}
