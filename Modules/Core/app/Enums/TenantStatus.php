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

    /**
     * The tenant lifecycle state machine. Archived is terminal.
     * Trialing→Trialing is the trial-extension transition.
     */
    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Trialing => in_array($to, [self::Trialing, self::Active, self::Suspended, self::Archived], true),
            self::Active => in_array($to, [self::Trialing, self::Suspended, self::Archived], true),
            self::Suspended => in_array($to, [self::Active, self::Archived], true),
            self::Archived => false,
        };
    }
}
