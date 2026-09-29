<?php

namespace Modules\Core\Contracts;

interface PlanContract
{
    public function getId(): int|string;

    public function getName(?string $locale = null): string;

    public function getSlug(): string;

    public function getPrice(): float;

    public function getBillingInterval(): string;

    public function getLimit(string $key, mixed $default = null): mixed;

    public function hasFeature(string $feature): bool;

    public function isFree(): bool;
}
