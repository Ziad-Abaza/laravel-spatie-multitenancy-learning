<?php

namespace Modules\Core\Enums;

enum Locale: string
{
    case English = 'en';
    case Arabic = 'ar';

    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Arabic => 'العربية',
        };
    }

    public function isRtl(): bool
    {
        return match ($this) {
            self::English => false,
            self::Arabic => true,
        };
    }
}
