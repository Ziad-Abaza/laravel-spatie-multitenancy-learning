<?php

namespace Modules\Core\Enums;

enum ThemePalette: string
{
    case Indigo = 'indigo';
    case Emerald = 'emerald';
    case Violet = 'violet';
    case Amber = 'amber';
    case Cyan = 'cyan';
    case Rose = 'rose';

    public function label(): string
    {
        return match ($this) {
            self::Indigo => 'Indigo',
            self::Emerald => 'Emerald',
            self::Violet => 'Violet',
            self::Amber => 'Amber',
            self::Cyan => 'Cyan',
            self::Rose => 'Rose',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
