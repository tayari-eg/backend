<?php

namespace App\Domains\Accounts\Enums;

enum Locale: string
{
    case Ar = 'ar';
    case En = 'en';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
