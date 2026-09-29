<?php

namespace Modules\Core\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';
    case Suspended = 'suspended';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Trialing => 'trialing',
            self::Active => 'active',
            self::PastDue => 'past_due',
            self::Canceled => 'canceled',
            self::Suspended => 'suspended',
            self::Expired => 'expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'emerald',
            self::Trialing => 'indigo',
            self::PastDue => 'rose',
            self::Canceled, self::Expired => 'slate',
            self::Suspended => 'amber',
        };
    }
}
