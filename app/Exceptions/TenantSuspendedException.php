<?php

namespace App\Exceptions;

use App\Models\Tenant;
use RuntimeException;

class TenantSuspendedException extends RuntimeException
{
    /**
     * The tenant was resolved successfully but its status does not allow
     * access (suspended, archived, or otherwise inactive).
     */
    public function __construct(public readonly ?Tenant $tenant = null)
    {
        parent::__construct('This workspace is currently suspended or its subscription has ended.');
    }

    public static function make(?Tenant $tenant = null): static
    {
        return new static($tenant);
    }
}
