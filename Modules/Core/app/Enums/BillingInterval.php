<?php

namespace Modules\Core\Enums;

enum BillingInterval: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'monthly',
            self::Yearly => 'yearly',
        };
    }
}
