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
     * The design's flow: an accepted order is picked up, the receiver confirms they are there (ongoing), then it is
     * delivered or not. Cancelling ends before pickup. picked_up → not_delivered is only for staff closing an order
     * whose receiver can't be reached (see tayarCanMoveTo()). Delivered, not delivered and cancelled are final.
     */
    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::LookingForTayar => in_array($next, [self::Pending, self::Cancelled], true),
            self::Pending => in_array($next, [self::PickedUp, self::Cancelled], true),
            self::PickedUp => in_array($next, [self::Ongoing, self::NotDelivered], true),
            self::Ongoing => in_array($next, [self::Delivered, self::NotDelivered], true),
            default => false,
        };
    }

    /**
     * The tayar's own steps: the refusal code is given at the door, so the tayar marks not delivered only once ongoing.
     */
    public function tayarCanMoveTo(self $next): bool
    {
        return in_array([$this, $next], [
            [self::Pending, self::PickedUp],
            [self::PickedUp, self::Ongoing],
            [self::Ongoing, self::Delivered],
            [self::Ongoing, self::NotDelivered],
        ], true) && $this->canTransitionTo($next);
    }

    /**
     * Staff close an order whose receiver can't be reached, without a code.
     */
    public function staffCanCloseAsNotDelivered(): bool
    {
        return in_array($this, [self::PickedUp, self::Ongoing], true) && $this->canTransitionTo(self::NotDelivered);
    }

    /**
     * The receiver's codes exist and can be resent only while the goods are with the tayar.
     */
    public function hasReceiverCodes(): bool
    {
        return in_array($this, [self::PickedUp, self::Ongoing], true);
    }

    /**
     * Senders and staff cancel until pickup (never after); the system only cancels an order no tayar accepted.
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
