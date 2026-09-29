<?php

namespace Modules\Core\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Contracts\IsTenant;

class TenantCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public IsTenant $tenant,
        public array $adminData = [],
        public ?int $planId = null
    ) {}
}
