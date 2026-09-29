<?php

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Contracts\SubscriptionContract;
use Modules\Core\Enums\SubscriptionStatus;
use Modules\Landlord\Models\Tenant;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Subscription extends Model implements SubscriptionContract
{
    use HasFactory, UsesLandlordConnection;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'status',
        'billing_interval',
        'amount',
        'currency',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'canceled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'amount' => 'float',
            'trial_ends_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'canceled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getTenantId(): int|string
    {
        return $this->tenant_id;
    }

    public function getPlanId(): int|string
    {
        return $this->plan_id;
    }

    public function getStatus(): string
    {
        return $this->status instanceof SubscriptionStatus ? $this->status->value : (string) $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active || $this->isTrialing();
    }

    public function isTrialing(): bool
    {
        return $this->status === SubscriptionStatus::Trialing || ($this->trial_ends_at && $this->trial_ends_at->isFuture());
    }

    public function isPastDue(): bool
    {
        return $this->status === SubscriptionStatus::PastDue;
    }

    public function isExpired(): bool
    {
        return $this->status === SubscriptionStatus::Expired || ($this->ends_at && $this->ends_at->isPast());
    }
}
