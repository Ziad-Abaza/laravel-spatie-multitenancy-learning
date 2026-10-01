<?php

namespace Modules\Landlord\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Contracts\TenantContract;
use Modules\Core\Enums\TenantStatus;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\Subscription;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements HasMedia, TenantContract
{
    use InteractsWithMedia, UsesLandlordConnection;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'database',
        'status',
        'plan_id',
        'settings',
        'trial_ends_at',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'status' => TenantStatus::class,
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'tenant_id');
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'tenant_id')->latestOfMany();
    }

    public function backups(): HasMany
    {
        return $this->hasMany(TenantBackup::class, 'tenant_id');
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug ?? $this->domain;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getDatabaseName(): string
    {
        return $this->database;
    }

    public function getStatus(): string
    {
        return $this->status instanceof TenantStatus ? $this->status->value : (string) $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === TenantStatus::Active || $this->status === TenantStatus::Trialing;
    }

    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::Suspended;
    }

    public function isTrialing(): bool
    {
        return $this->status === TenantStatus::Trialing || ($this->trial_ends_at && $this->trial_ends_at->isFuture());
    }

    public function getQuota(string $key, mixed $default = null): mixed
    {
        return $this->plan?->getLimit($key, $default) ?? $default;
    }

    public function hasFeature(string $feature): bool
    {
        return $this->plan?->hasFeature($feature) ?? false;
    }

    /**
     * Get the full URL for the tenant.
     */
    public function url(string $path = '/'): string
    {
        $scheme = request()->getScheme();
        $port = request()->getPort();
        $portSuffix = ($port && ! in_array($port, [80, 443])) ? ":{$port}" : '';

        return "{$scheme}://{$this->domain}{$portSuffix}/".ltrim($path, '/');
    }
}
