<?php

namespace Modules\Core\Contracts;

interface SettingManagerContract
{
    public function get(string $key, mixed $default = null, string $domain = 'system'): mixed;

    public function set(string $key, mixed $value, string $domain = 'system'): void;

    /**
     * @return array<string, mixed>
     */
    public function allByDomain(string $domain): array;
}
