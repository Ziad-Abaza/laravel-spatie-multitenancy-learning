<?php

namespace Modules\Core\Contracts;

interface QuotaManagerContract
{
    public function canAddUser(mixed $tenant): bool;

    public function canUseFeature(mixed $tenant, string $feature): bool;

    public function getUserLimit(mixed $tenant): ?int;

    public function getUserCount(mixed $tenant): int;

    public function getStorageLimitMb(mixed $tenant): ?int;
}
