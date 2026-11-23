<?php

namespace App\Domains\Catalog\Enums;

/**
 * The fixed ways a tayar can work. Behaviour hangs on the code, so modes are seeded, never created by staff.
 */
enum ModeCode: string
{
    case LongDistance = 'long_distance';
    case Zone = 'zone';
    case Area = 'area';

    public function requiresArea(): bool
    {
        return $this === self::Area;
    }

    public function requiresRadius(): bool
    {
        return $this === self::Zone;
    }

    public function requiresDestination(): bool
    {
        return $this === self::LongDistance;
    }

    /**
     * How many accepted orders a tayar working in this mode may hold at once; a long-distance tayar carries several.
     */
    public function maxActiveOrders(): int
    {
        return $this === self::LongDistance
            ? config()->integer('tayari.matching.max_active_orders_long_distance')
            : config()->integer('tayari.matching.max_active_orders');
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
