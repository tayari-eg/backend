<?php

namespace App\Domains\Orders\Enums;

/**
 * The delivery lifecycle of an order. Payment is tracked separately.
 */
enum OrderStatus: string
{
    case LookingForTayar = 'looking_for_tayar';
    case Pending = 'pending';
    case PickedUp = 'picked_up';
    case Ongoing = 'ongoing';
    case Delivered = 'delivered';
    case NotDelivered = 'not_delivered';
    case Cancelled = 'cancelled';

    /**
     * Only the transitions built so far; each later phase adds its own.
     */
    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::LookingForTayar => $next === self::Cancelled,
            default => false,
        };
    }

    public function isActive(): bool
    {
        return in_array($this, self::activeCases(), true);
    }

    /**
     * @return list<self>
     */
    public static function activeCases(): array
    {
        return [self::LookingForTayar, self::Pending, self::PickedUp, self::Ongoing];
    }

    /**
     * @return list<self>
     */
    public static function pastCases(): array
    {
        return [self::Delivered, self::NotDelivered, self::Cancelled];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
