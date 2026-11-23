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
            self::LookingForTayar => in_array($next, [self::Pending, self::Cancelled], true),
            self::Pending => $next === self::Cancelled,
            default => false,
        };
    }

    /**
     * Senders and staff cancel until pickup; the system only cancels an order no tayar accepted.
     */
    public function canBeCancelledBy(OrderCanceller $canceller): bool
    {
        return $this->canTransitionTo(self::Cancelled) && ($canceller !== OrderCanceller::System || $this === self::LookingForTayar);
    }

    /**
     * @return list<self>
     */
    public static function withTayarCases(): array
    {
        return [self::Pending, self::PickedUp, self::Ongoing];
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
