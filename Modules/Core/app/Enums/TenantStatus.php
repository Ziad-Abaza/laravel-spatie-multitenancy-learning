<?php

namespace Modules\Core\Enums;

enum TenantStatus: string
{
    case Active = 'active';
    case Trialing = 'trialing';
    case Suspended = 'suspended';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'active',
            self::Trialing => 'trialing',
            self::Suspended => 'suspended',
            self::Archived => 'archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'emerald',
            self::Trialing => 'indigo',
            self::Suspended => 'amber',
            self::Archived => 'slate',
        };
    }
}
