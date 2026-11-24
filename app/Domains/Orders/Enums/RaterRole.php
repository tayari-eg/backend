<?php

namespace App\Domains\Orders\Enums;

/**
 * Who gave a rating: the business that sent the order rates its tayar, and the tayar rates the business.
 */
enum RaterRole: string
{
    case Business = 'business';
    case Tayar = 'tayar';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
