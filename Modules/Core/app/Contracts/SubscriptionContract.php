<?php

namespace Modules\Core\Contracts;

interface SubscriptionContract
{
    public function getId(): int|string;

    public function getTenantId(): int|string;

    public function getPlanId(): int|string;

    public function getStatus(): string;

    public function isActive(): bool;

    public function isTrialing(): bool;

    public function isPastDue(): bool;

    public function isExpired(): bool;
}
