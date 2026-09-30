<?php

namespace Modules\Core\Enums;

enum Currency: string
{
    case Usd = 'USD';
    case Eur = 'EUR';
    case Gbp = 'GBP';
    case Sar = 'SAR';
    case Aed = 'AED';
    case Egp = 'EGP';
    case Kwd = 'KWD';
    case Bhd = 'BHD';
    case Qar = 'QAR';
    case Jod = 'JOD';
    case Try = 'TRY';
    case Inr = 'INR';
    case Jpy = 'JPY';
    case Aud = 'AUD';
    case Cad = 'CAD';

    public function label(): string
    {
        return match ($this) {
            self::Usd => 'US Dollar',
            self::Eur => 'Euro',
            self::Gbp => 'British Pound',
            self::Sar => 'Saudi Riyal',
            self::Aed => 'UAE Dirham',
            self::Egp => 'Egyptian Pound',
            self::Kwd => 'Kuwaiti Dinar',
            self::Bhd => 'Bahraini Dinar',
            self::Qar => 'Qatari Riyal',
            self::Jod => 'Jordanian Dinar',
            self::Try => 'Turkish Lira',
            self::Inr => 'Indian Rupee',
            self::Jpy => 'Japanese Yen',
            self::Aud => 'Australian Dollar',
            self::Cad => 'Canadian Dollar',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Options for selects: [value => "USD — US Dollar"].
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $currency) => [$currency->value => $currency->value.' — '.$currency->label()])
            ->all();
    }
}
