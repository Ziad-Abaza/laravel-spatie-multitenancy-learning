<?php

namespace Modules\Subscription\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Contracts\PlanContract;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Translatable\HasTranslations;

class Plan extends Model implements PlanContract
{
    use HasFactory, HasTranslations, UsesLandlordConnection;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_interval',
        'is_active',
        'trial_days',
        'limits',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'is_active' => 'boolean',
            'trial_days' => 'integer',
            'limits' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'plan_id');
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getName(?string $locale = null): string
    {
        return $this->getTranslation('name', $locale ?? app()->getLocale());
    }

    public function getDescription(?string $locale = null): ?string
    {
        return $this->getTranslation('description', $locale ?? app()->getLocale());
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getPrice(): float
    {
        return (float) $this->price;
    }

    public function getBillingInterval(): string
    {
        return $this->billing_interval ?? 'monthly';
    }

    public function getLimit(string $key, mixed $default = null): mixed
    {
        return $this->limits[$key] ?? $default;
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->limits['features'] ?? [];
        if (! is_array($features)) {
            return false;
        }

        return in_array($feature, $features, true) || in_array('*', $features, true);
    }

    public function isFree(): bool
    {
        return $this->getPrice() <= 0.0;
    }
}
