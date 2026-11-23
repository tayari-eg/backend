<?php

namespace App\Domains\Orders\Models;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Orders\Enums\OrderCanceller;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Enums\ShippingPaidBy;
use App\Domains\Tayars\Models\TayarWorkSession;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One pickup and one drop-off. The fee columns are a snapshot of the pricing at creation.
 */
#[Fillable([
    'idempotency_key', 'category_id', 'mode_id', 'details',
    'pickup_address', 'pickup_lat', 'pickup_lng', 'pickup_contact_phone',
    'dropoff_address', 'dropoff_lat', 'dropoff_lng',
    'receiver_name', 'receiver_phone', 'receiver_user_id',
    'goods_value', 'shipping_paid_by', 'amount_to_collect',
    'distance_km', 'base_fare', 'price_per_km', 'distance_fare', 'minimum_fare', 'food_extra',
    'shipping_fee', 'tayar_share_percent', 'tayar_earning', 'platform_fee',
])]
#[UseFactory(OrderFactory::class)]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, SoftDeletes;

    public const string CODE_PREFIX = 'TY-';

    /**
     * No 0/O or 1/I, so a code read out on the phone is not misheard.
     */
    private const string CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const int CODE_LENGTH = 6;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'cancel_fee' => '0.00',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sender_id' => 'integer',
            'category_id' => 'integer',
            'mode_id' => 'integer',
            'receiver_user_id' => 'integer',
            'tayar_id' => 'integer',
            'work_session_id' => 'integer',
            'pickup_lat' => 'decimal:7',
            'pickup_lng' => 'decimal:7',
            'dropoff_lat' => 'decimal:7',
            'dropoff_lng' => 'decimal:7',
            'goods_value' => 'decimal:2',
            'shipping_paid_by' => ShippingPaidBy::class,
            'amount_to_collect' => 'decimal:2',
            'distance_km' => 'decimal:2',
            'base_fare' => 'decimal:2',
            'price_per_km' => 'decimal:2',
            'distance_fare' => 'decimal:2',
            'minimum_fare' => 'decimal:2',
            'food_extra' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'tayar_share_percent' => 'decimal:2',
            'tayar_earning' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'status' => OrderStatus::class,
            'price_accepted_at' => 'datetime',
            'accepted_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'cancelled_by' => 'integer',
            'cancel_reason_id' => 'integer',
            'cancel_fee' => 'decimal:2',
            'cancel_fee_owed_to_tayar_id' => 'integer',
        ];
    }

    public static function newCode(): string
    {
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
        }

        return self::CODE_PREFIX.$code;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Mode, $this>
     */
    public function mode(): BelongsTo
    {
        return $this->belongsTo(Mode::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @return BelongsTo<OrderReason, $this>
     */
    public function cancelReason(): BelongsTo
    {
        return $this->belongsTo(OrderReason::class, 'cancel_reason_id');
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
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
     * @return HasMany<OrderOffer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(OrderOffer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cancelFeeOwedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancel_fee_owed_to_tayar_id');
    }

    /**
     * Until when the sender cancels an accepted order for free; null when the order is not accepted (pending).
     */
    public function freeCancelUntil(): ?CarbonImmutable
    {
        if ($this->status !== OrderStatus::Pending || $this->accepted_at === null) {
            return null;
        }

        return $this->accepted_at->toImmutable()->addSeconds(config()->integer('tayari.matching.free_cancel_seconds'));
    }

    /**
     * What the sender owes the tayar for cancelling at that moment: nothing before a tayar accepts or within the free
     * period (its last second included), then tayari.matching.cancel_fee, at most the shipping fee.
     */
    public function senderCancelFeeAt(CarbonInterface $moment): BigDecimal
    {
        $freeUntil = $this->freeCancelUntil();

        if ($freeUntil === null || $moment->lte($freeUntil)) {
            return BigDecimal::zero()->toScale(2);
        }

        return BigDecimal::min(BigDecimal::of((string) config('tayari.matching.cancel_fee')), BigDecimal::of($this->shipping_fee))->toScale(2, RoundingMode::HalfUp);
    }

    public function canBeCancelledBy(OrderCanceller $canceller): bool
    {
        return $this->status->canBeCancelledBy($canceller);
    }

    /**
     * Who cancelled the order: nobody (null) while it is not cancelled; no cancelled_by means the system.
     */
    public function cancelledByType(): ?OrderCanceller
    {
        if ($this->status !== OrderStatus::Cancelled) {
            return null;
        }

        return match ($this->cancelled_by) {
            null => OrderCanceller::System,
            $this->sender_id => OrderCanceller::Sender,
            default => OrderCanceller::Staff,
        };
    }

    /**
     * When the system stops looking for a tayar and cancels the order (tayari.matching.search_timeout_minutes).
     */
    public function searchEndsAt(): CarbonImmutable
    {
        return $this->price_accepted_at->toImmutable()->addMinutes(config()->integer('tayari.matching.search_timeout_minutes'));
    }

    /**
     * @param  Builder<self>  $query
     * @param  list<OrderStatus>  $statuses
     */
    #[Scope]
    protected function whereStatusIn(Builder $query, array $statuses): void
    {
        $query->whereIn('status', array_map(fn (OrderStatus $status): string => $status->value, $statuses));
    }
}
