<?php

namespace App\Domains\Orders\Models;

use App\Domains\Orders\Enums\OfferStatus;
use App\Domains\Tayars\Models\TayarWorkSession;
use App\Models\User;
use Database\Factories\OrderOfferFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One order offered to one tayar for one round. An offered row past expires_at is already expired: the sweep only
 * writes that later, so reads use live() or effectiveStatus(), and writes check the time under the lock.
 */
#[UseFactory(OrderOfferFactory::class)]
class OrderOffer extends Model
{
    /** @use HasFactory<OrderOfferFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'tayar_id' => 'integer',
            'work_session_id' => 'integer',
            'round' => 'integer',
            'distance_km' => 'decimal:2',
            'tayar_share_percent' => 'decimal:2',
            'tayar_earning' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'status' => OfferStatus::class,
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function tayar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tayar_id');
    }

    /**
     * @return BelongsTo<TayarWorkSession, $this>
     */
    public function workSession(): BelongsTo
    {
        return $this->belongsTo(TayarWorkSession::class);
    }

    /**
     * Moves this one offer to the next status and saves it; OfferStatus decides which moves are allowed.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws LogicException when the offer can't move to that status
     */
    public function moveTo(OfferStatus $next, array $attributes = []): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw new LogicException("Offer {$this->id} can't move from {$this->status->value} to {$next->value}.");
        }

        $this->forceFill(['status' => $next, ...$attributes])->save();
    }

    public function isLive(): bool
    {
        return $this->status === OfferStatus::Offered && $this->expires_at->isFuture();
    }

    public function effectiveStatus(): OfferStatus
    {
        return $this->status === OfferStatus::Offered && ! $this->isLive() ? OfferStatus::Expired : $this->status;
    }

    /**
     * Offered and not past expires_at.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function live(Builder $query): void
    {
        $query->where('order_offers.status', OfferStatus::Offered)->where('order_offers.expires_at', '>', now());
    }

    /**
     * Offered but past expires_at, still waiting for the sweep to write expired.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->where('order_offers.status', OfferStatus::Offered)->where('order_offers.expires_at', '<=', now());
    }
}
