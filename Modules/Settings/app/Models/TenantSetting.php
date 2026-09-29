<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class TenantSetting extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $table = 'tenant_settings';

    protected $fillable = [
        'domain',
        'key',
        'value',
        'type',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function getParsedValue(): mixed
    {
        return match ($this->type) {
            'json', 'array' => json_decode($this->value, true),
            'boolean', 'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $this->value,
            'float', 'double' => (float) $this->value,
            default => $this->value,
        };
    }

    public static function serializeValue(mixed $value): array
    {
        if (is_array($value) || is_object($value)) {
            return ['type' => 'json', 'value' => json_encode($value)];
        }

        if (is_bool($value)) {
            return ['type' => 'boolean', 'value' => $value ? '1' : '0'];
        }

        if (is_int($value)) {
            return ['type' => 'integer', 'value' => (string) $value];
        }

        if (is_float($value)) {
            return ['type' => 'float', 'value' => (string) $value];
        }

        return ['type' => 'string', 'value' => (string) $value];
    }
}
