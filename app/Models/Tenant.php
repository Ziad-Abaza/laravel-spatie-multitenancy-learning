<?php

namespace App\Models;

use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'domain',
        'database',
        'db_username',
        'db_password',
    ];

    /**
     * Get the full URL for the tenant.
     */
    public function url(string $path = '/'): string
    {
        $scheme = request()->getScheme();
        $port = request()->getPort();
        $portSuffix = ($port && ! in_array($port, [80, 443])) ? ":{$port}" : '';

        return "{$scheme}://{$this->domain}{$portSuffix}/" . ltrim($path, '/');
    }
}
