<?php

namespace Modules\Core\Contracts;

use Spatie\Multitenancy\Contracts\IsTenant;

interface TenantContract extends IsTenant
{
    public function getId(): int|string;

    public function getName(): string;

    public function getSlug(): string;

    public function getDomain(): string;

    public function getDatabaseName(): string;

    public function getStatus(): string;

    public function isActive(): bool;

    public function isSuspended(): bool;

    public function isTrialing(): bool;
}
