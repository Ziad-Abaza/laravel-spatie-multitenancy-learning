<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Settings\Models\Concerns\ParsesSettingValue;
use Modules\Settings\Services\SettingService;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Setting extends Model
{
    use HasFactory, ParsesSettingValue, UsesLandlordConnection;

    protected $table = 'settings';

    protected $fillable = [
        'domain',
        'key',
        'value',
        'type',
        'is_public',
    ];

    protected static function booted(): void
    {
        $invalidate = fn (self $model) => SettingService::forgetMapForModel(static::class, $model->domain);

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }
}
